<?php

namespace App\Http\Controllers;

use App\Jobs\RunAutomationRulesJob;
use App\Models\AdAccount;
use App\Models\AdInsightDaily;
use App\Models\AdSet;
use App\Models\AgentAuditLog;
use App\Models\Campaign;
use App\Services\Analytics\CampaignHealthEvaluator;
use App\Services\Meta\CampaignActionService;
use App\Services\Meta\MetaAdsClientService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected CampaignHealthEvaluator $evaluator,
        protected CampaignActionService $actionService,
        protected MetaAdsClientService $clientService
    ) {
    }

    public function index(): View
    {
        $account = AdAccount::first();
        $campaigns = Campaign::with('adSets')->get();
        $adsets = AdSet::with(['campaign', 'adAccount'])->get();

        // Evaluasi semua adset dengan Smart Engine
        $evaluatedAdsets = [];
        foreach ($adsets as $adset) {
            $evaluation = $this->evaluator->evaluateAdset($adset, 3);
            $evaluatedAdsets[] = array_merge(['model' => $adset], $evaluation);
        }

        // Summary Metrik
        $totalSpend = AdInsightDaily::sum('spend');
        $totalConversions = AdInsightDaily::sum('conversions');
        $totalRevenue = AdInsightDaily::sum('conversion_value');
        $overallRoas = $totalSpend > 0 ? ($totalRevenue / $totalSpend) : 0;
        $overallCpa = $totalConversions > 0 ? ($totalSpend / $totalConversions) : 0;

        $auditLogs = AgentAuditLog::latest()->take(10)->get();

        return view('dashboard', [
            'account' => $account,
            'campaigns' => $campaigns,
            'evaluatedAdsets' => $evaluatedAdsets,
            'kpis' => [
                'total_spend' => $totalSpend,
                'total_conversions' => $totalConversions,
                'total_revenue' => $totalRevenue,
                'overall_roas' => $overallRoas,
                'overall_cpa' => $overallCpa,
            ],
            'auditLogs' => $auditLogs,
        ]);
    }

    public function updateStatus(Request $request, string $metaAdsetId): RedirectResponse
    {
        $status = $request->input('status', 'PAUSED');
        $this->actionService->setAdsetStatus($metaAdsetId, $status);

        AgentAuditLog::create([
            'agent_id' => 'user_manual',
            'action' => "UPDATE_STATUS_{$status}",
            'target_type' => 'ADSET',
            'target_id' => $metaAdsetId,
            'reason' => 'Tindakan manual dari web dashboard',
            'payload' => ['new_status' => $status],
        ]);

        return redirect()->back()->with('success', "Status AdSet {$metaAdsetId} berhasil diubah ke {$status}.");
    }

    public function scaleBudget(Request $request, string $metaAdsetId): RedirectResponse
    {
        $pct = (float) $request->input('percentage', 20.0);
        $res = $this->actionService->adjustAdsetBudget($metaAdsetId, $pct);

        AgentAuditLog::create([
            'agent_id' => 'user_manual',
            'action' => 'SCALE_BUDGET',
            'target_type' => 'ADSET',
            'target_id' => $metaAdsetId,
            'reason' => "Manual scaling budget sebesar {$pct}%",
            'payload' => $res,
        ]);

        return redirect()->back()->with('success', "Budget AdSet {$metaAdsetId} dinaikkan {$pct}% menjadi Rp " . number_format($res['new_budget'], 0, ',', '.'));
    }

    public function runWatchdogNow(): RedirectResponse
    {
        RunAutomationRulesJob::dispatchSync($this->evaluator, $this->actionService);
        return redirect()->back()->with('success', 'Watchdog Otomasi Kill-Switch & Scale berhasil dieksekusi.');
    }

    public function connectManual(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'meta_account_id' => 'required|string',
            'access_token' => 'required|string',
            'target_roas' => 'nullable|numeric',
            'target_cpa' => 'nullable|numeric',
        ]);

        $metaAccountId = preg_replace('/^act_/', '', trim($validated['meta_account_id']));

        $account = AdAccount::updateOrCreate(
            ['meta_account_id' => $metaAccountId],
            [
                'name' => "Meta Account act_{$metaAccountId}",
                'access_token' => $validated['access_token'],
                'target_roas' => $validated['target_roas'] ?? 2.50,
                'target_cpa' => $validated['target_cpa'] ?? 100000.00,
                'status' => 'ACTIVE',
            ]
        );

        try {
            $api = \FacebookAds\Api::init(
                config('meta.app_id') ?: 'dummy_app_id',
                config('meta.app_secret') ?: 'dummy_secret',
                $validated['access_token']
            );
            $metaAccount = new \FacebookAds\Object\AdAccount('act_' . $metaAccountId);
            $accountData = $metaAccount->read([
                \FacebookAds\Object\Fields\AdAccountFields::NAME,
                \FacebookAds\Object\Fields\AdAccountFields::CURRENCY,
                \FacebookAds\Object\Fields\AdAccountFields::TIMEZONE_NAME,
            ]);

            $account->update([
                'name' => $accountData->{\FacebookAds\Object\Fields\AdAccountFields::NAME} ?? $account->name,
                'currency' => $accountData->{\FacebookAds\Object\Fields\AdAccountFields::CURRENCY} ?? 'IDR',
                'timezone_name' => $accountData->{\FacebookAds\Object\Fields\AdAccountFields::TIMEZONE_NAME} ?? 'Asia/Jakarta',
            ]);

            $campaigns = $metaAccount->getCampaigns([
                \FacebookAds\Object\Fields\CampaignFields::ID,
                \FacebookAds\Object\Fields\CampaignFields::NAME,
                \FacebookAds\Object\Fields\CampaignFields::OBJECTIVE,
                \FacebookAds\Object\Fields\CampaignFields::STATUS,
                \FacebookAds\Object\Fields\CampaignFields::DAILY_BUDGET,
            ]);

            // Langsung sinkronkan campaigns, adsets, dan insights hari ini secara instan
            $job = new \App\Jobs\SyncMetaInsightsJob($account->id, 'today');
            $job->handle($this->clientService);

            return redirect()->route('dashboard')->with('success', "🎉 Berhasil terhubung ke akun '{$account->name}' via Token! Semua Campaign, AdSet, dan Insights performa berhasil disinkronkan secara langsung.");
        } catch (\Throwable $e) {
            return redirect()->route('dashboard')->with('success', "Akun 'act_{$metaAccountId}' tersimpan di database lokal. Catatan koneksi: " . $e->getMessage());
        }
    }

    public function syncData(Request $request): RedirectResponse
    {
        $preset = $request->input('preset', 'today');
        
        $job = new \App\Jobs\SyncMetaInsightsJob(null, $preset);
        $job->handle($this->clientService);

        $presetLabels = [
            'today' => 'Hari Ini',
            'yesterday' => 'Kemarin',
            'last_3d' => '3 Hari Terakhir',
            'last_7d' => '7 Hari Terakhir',
            'last_30d' => '30 Hari Terakhir',
        ];
        $label = $presetLabels[$preset] ?? $preset;

        return redirect()->route('dashboard')->with('success', "🔄 Berhasil menyinkronkan data performa ({$label}) langsung dari Meta Ads!");
    }

    public function createCampaign(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'objective' => 'required|string',
            'daily_budget' => 'nullable|numeric|min:10000',
            'status' => 'required|string|in:PAUSED,ACTIVE',
            'adset_name' => 'nullable|string|max:255',
            'adset_budget' => 'nullable|numeric|min:10000',
        ]);

        $account = AdAccount::where('status', 'ACTIVE')->first();
        if (!$account) {
            return redirect()->route('dashboard')->with('error', 'Belum ada akun iklan aktif yang terhubung. Silakan hubungkan akun terlebih dahulu.');
        }

        $result = $this->actionService->createCampaign($account, $validated);

        AgentAuditLog::create([
            'agent_id' => 'user_dashboard',
            'action' => 'CREATE_CAMPAIGN',
            'target_type' => 'CAMPAIGN',
            'target_id' => $result['campaign']->meta_campaign_id,
            'reason' => "Membuat campaign baru '{$validated['name']}' dengan objektif {$validated['objective']} dari web dashboard",
            'payload' => $result,
        ]);

        return redirect()->route('dashboard')->with('success', $result['message']);
    }
}
