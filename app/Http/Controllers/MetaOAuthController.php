<?php

namespace App\Http\Controllers;

use App\Models\AdAccount;
use App\Models\Campaign;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MetaOAuthController extends Controller
{
    /**
     * Redirect user to Meta Facebook OAuth Dialog.
     */
    public function redirect(): RedirectResponse
    {
        $appId = config('meta.app_id');
        $redirectUri = config('meta.redirect_uri');
        $apiVersion = config('meta.api_version', 'v20.0');
        $scopes = implode(',', config('meta.scopes', [
            'ads_management',
            'ads_read',
            'read_insights',
            'business_management',
        ]));

        if (empty($appId)) {
            return redirect()->route('dashboard')->with('error', 'META_APP_ID belum diisi di file .env Anda.');
        }

        $state = Str::random(40);
        session(['meta_oauth_state' => $state]);

        $query = http_build_query([
            'client_id' => $appId,
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'scope' => $scopes,
            'response_type' => 'code',
        ]);

        return redirect()->away("https://www.facebook.com/{$apiVersion}/dialog/oauth?{$query}");
    }

    /**
     * Handle OAuth Callback from Meta.
     */
    public function callback(Request $request): RedirectResponse
    {
        if ($request->has('error')) {
            $reason = $request->input('error_description', $request->input('error_reason', 'Otentikasi dibatalkan.'));
            return redirect()->route('dashboard')->with('error', "Gagal login Meta OAuth: {$reason}");
        }

        $state = $request->input('state');
        $savedState = session('meta_oauth_state');

        if (empty($state) || $state !== $savedState) {
            return redirect()->route('dashboard')->with('error', 'Invalid OAuth State (CSRF check failed). Silakan coba lagi.');
        }

        session()->forget('meta_oauth_state');

        $code = $request->input('code');
        $appId = config('meta.app_id');
        $appSecret = config('meta.app_secret');
        $redirectUri = config('meta.redirect_uri');
        $apiVersion = config('meta.api_version', 'v20.0');

        try {
            // 1. Tukar authorization code dengan Short-Lived Access Token
            $tokenResponse = Http::asForm()->get("https://graph.facebook.com/{$apiVersion}/oauth/access_token", [
                'client_id' => $appId,
                'client_secret' => $appSecret,
                'redirect_uri' => $redirectUri,
                'code' => $code,
            ]);

            if ($tokenResponse->failed()) {
                $err = $tokenResponse->json('error.message', 'Gagal menukar code dengan access token.');
                return redirect()->route('dashboard')->with('error', "Meta OAuth Error: {$err}");
            }

            $shortToken = $tokenResponse->json('access_token');

            // 2. Tukar Short-Lived Token dengan Long-Lived Token (berlaku 60 hari)
            $longTokenResponse = Http::get("https://graph.facebook.com/{$apiVersion}/oauth/access_token", [
                'grant_type' => 'fb_exchange_token',
                'client_id' => $appId,
                'client_secret' => $appSecret,
                'fb_exchange_token' => $shortToken,
            ]);

            $finalToken = $longTokenResponse->successful()
                ? $longTokenResponse->json('access_token')
                : $shortToken;

            // 3. Ambil daftar Ad Accounts pengguna (/me/adaccounts)
            $accountsResponse = Http::get("https://graph.facebook.com/{$apiVersion}/me/adaccounts", [
                'access_token' => $finalToken,
                'fields' => 'id,account_id,name,currency,timezone_name,account_status',
            ]);

            if ($accountsResponse->failed()) {
                $err = $accountsResponse->json('error.message', 'Gagal mengambil data ad accounts.');
                return redirect()->route('dashboard')->with('error', "Gagal mengambil Ad Accounts: {$err}");
            }

            $adAccountsData = $accountsResponse->json('data', []);

            if (empty($adAccountsData)) {
                return redirect()->route('dashboard')->with('error', 'Akun Meta Anda berhasil terhubung, namun tidak ada Ad Account yang ditemukan.');
            }

            $importedAccounts = [];

            // 4. Simpan setiap Ad Account ke database
            foreach ($adAccountsData as $item) {
                $rawId = $item['account_id'] ?? preg_replace('/^act_/', '', $item['id']);
                $name = $item['name'] ?? "Ad Account act_{$rawId}";
                $currency = $item['currency'] ?? 'IDR';
                $timezone = $item['timezone_name'] ?? 'Asia/Jakarta';

                $account = AdAccount::updateOrCreate(
                    ['meta_account_id' => $rawId],
                    [
                        'name' => $name,
                        'currency' => $currency,
                        'timezone_name' => $timezone,
                        'access_token' => $finalToken,
                        'target_roas' => 2.50,
                        'target_cpa' => 100000.00,
                        'status' => 'ACTIVE',
                    ]
                );

                $importedAccounts[] = $name;

                // Tarik campaign aktif untuk akun ini
                $campResponse = Http::get("https://graph.facebook.com/{$apiVersion}/act_{$rawId}/campaigns", [
                    'access_token' => $finalToken,
                    'fields' => 'id,name,objective,status,daily_budget',
                    'limit' => 50,
                ]);

                if ($campResponse->successful()) {
                    foreach ($campResponse->json('data', []) as $camp) {
                        Campaign::updateOrCreate(
                            ['meta_campaign_id' => $camp['id']],
                            [
                                'ad_account_id' => $account->id,
                                'name' => $camp['name'],
                                'objective' => $camp['objective'] ?? 'OUTCOME_SALES',
                                'status' => $camp['status'] ?? 'ACTIVE',
                                'daily_budget' => isset($camp['daily_budget']) ? ($camp['daily_budget'] / 100) : null,
                            ]
                        );
                    }
                }
            }

            $accountListStr = implode(', ', $importedAccounts);
            return redirect()->route('dashboard')->with(
                'success',
                "🎉 Berhasil terhubung via Meta OAuth 2.0! Akun terhubung: {$accountListStr} (Token otomatis diperpanjang hingga 60 hari)."
            );

        } catch (\Throwable $e) {
            Log::error("Meta OAuth Exception: {$e->getMessage()}");
            return redirect()->route('dashboard')->with('error', "Terjadi kesalahan saat memproses OAuth: {$e->getMessage()}");
        }
    }
}
