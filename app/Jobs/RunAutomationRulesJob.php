<?php

namespace App\Jobs;

use App\Models\AdSet;
use App\Models\AgentAuditLog;
use App\Services\Analytics\CampaignHealthEvaluator;
use App\Services\Meta\CampaignActionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class RunAutomationRulesJob implements ShouldQueue
{
    use Queueable;

    public function handle(
        CampaignHealthEvaluator $evaluator,
        CampaignActionService $actionService
    ): void {
        $activeAdsets = AdSet::where('status', 'ACTIVE')->get();

        foreach ($activeAdsets as $adset) {
            $analysis = $evaluator->evaluateAdset($adset, 3);
            $verdict = $analysis['verdict'];

            // 1. Auto-Kill Switch jika boncos (LOSING_MONEY)
            if ($verdict === 'LOSING_MONEY') {
                $reason = "Auto-Kill Switch triggered: " . implode(' ', $analysis['reasons']);
                $actionService->setAdsetStatus($adset->meta_adset_id, 'PAUSED');

                AgentAuditLog::create([
                    'agent_id' => 'system_kill_switch',
                    'action' => 'PAUSE_ADSET',
                    'target_type' => 'ADSET',
                    'target_id' => $adset->meta_adset_id,
                    'reason' => $reason,
                    'payload' => $analysis,
                ]);

                Log::warning("Automation Watchdog: Paused AdSet {$adset->meta_adset_id} ({$adset->name}) - {$reason}");
            }

            // 2. Auto-Scale jika winning (SCALING_CANDIDATE)
            elseif ($verdict === 'SCALING_CANDIDATE') {
                // Scale budget 15% secara aman
                $result = $actionService->adjustAdsetBudget($adset->meta_adset_id, 15.0);
                $reason = "Auto-Scale triggered: High ROAS ({$analysis['metrics']['roas']}x). Budget raised 15% to {$result['new_budget']}.";

                AgentAuditLog::create([
                    'agent_id' => 'system_autoscale',
                    'action' => 'INCREASE_BUDGET',
                    'target_type' => 'ADSET',
                    'target_id' => $adset->meta_adset_id,
                    'reason' => $reason,
                    'payload' => array_merge($analysis, $result),
                ]);

                Log::info("Automation Watchdog: Scaled AdSet {$adset->meta_adset_id} ({$adset->name}) - {$reason}");
            }
        }
    }
}
