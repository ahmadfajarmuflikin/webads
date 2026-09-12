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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected CampaignHealthEvaluator $evaluator,
        protected CampaignActionService $actionService
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
}
