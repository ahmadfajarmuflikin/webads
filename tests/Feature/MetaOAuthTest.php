<?php

namespace Tests\Feature;

use App\Models\AdAccount;
use Database\Seeders\MetaAdsDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MetaOAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MetaAdsDemoSeeder::class);
    }

    public function test_meta_oauth_redirect_generates_valid_facebook_dialog_url(): void
    {
        config(['meta.app_id' => '123456789012345']);

        $response = $this->get(route('auth.meta.redirect'));

        $response->assertRedirect();
        $targetUrl = $response->headers->get('Location');
        $this->assertStringContainsString('https://www.facebook.com/v20.0/dialog/oauth', $targetUrl);
        $this->assertStringContainsString('client_id=123456789012345', $targetUrl);
        $this->assertStringContainsString('ads_management', $targetUrl);
    }

    public function test_meta_oauth_callback_rejects_mismatched_state(): void
    {
        $response = $this->withSession(['meta_oauth_state' => 'valid_state_123'])
            ->get(route('auth.meta.callback', [
                'state' => 'wrong_state_456',
                'code' => 'fake_auth_code',
            ]));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('error');
    }

    public function test_meta_oauth_callback_successfully_imports_ad_accounts(): void
    {
        config([
            'meta.app_id' => '123456789012345',
            'meta.app_secret' => 'mock_secret_key',
        ]);

        Http::fake([
            'https://graph.facebook.com/v20.0/oauth/access_token*' => Http::response([
                'access_token' => 'mock_long_lived_user_token_60d',
                'token_type' => 'bearer',
            ], 200),
            'https://graph.facebook.com/v20.0/me/adaccounts*' => Http::response([
                'data' => [
                    [
                        'id' => 'act_999888777',
                        'account_id' => '999888777',
                        'name' => 'OAuth Connected Brand Store',
                        'currency' => 'IDR',
                        'timezone_name' => 'Asia/Jakarta',
                        'account_status' => 1,
                    ],
                ],
            ], 200),
            'https://graph.facebook.com/v20.0/act_999888777/campaigns*' => Http::response([
                'data' => [
                    [
                        'id' => 'camp_oauth_101',
                        'name' => 'Live Promo Campaign',
                        'objective' => 'OUTCOME_SALES',
                        'status' => 'ACTIVE',
                        'daily_budget' => 25000000,
                    ]
                ]
            ], 200),
        ]);

        $response = $this->withSession(['meta_oauth_state' => 'secure_random_state'])
            ->get(route('auth.meta.callback', [
                'state' => 'secure_random_state',
                'code' => 'valid_mock_code_from_facebook',
            ]));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('ad_accounts', [
            'meta_account_id' => '999888777',
            'name' => 'OAuth Connected Brand Store',
        ]);

        $this->assertDatabaseHas('campaigns', [
            'meta_campaign_id' => 'camp_oauth_101',
            'name' => 'Live Promo Campaign',
        ]);
    }
}
