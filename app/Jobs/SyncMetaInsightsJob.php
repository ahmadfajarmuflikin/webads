<?php

namespace App\Jobs;

use App\Models\AdAccount;
use App\Models\AdInsightDaily;
use App\Services\Meta\MetaAdsClientService;
use FacebookAds\Object\AdAccount as MetaAccount;
use FacebookAds\Object\Fields\AdsInsightsFields;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncMetaInsightsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public ?int $adAccountId = null, public string $datePreset = 'today')
    {
    }

    public function handle(MetaAdsClientService $clientService): void
    {
        $accounts = $this->adAccountId
            ? AdAccount::where('id', $this->adAccountId)->get()
            : AdAccount::where('status', 'ACTIVE')->get();

        foreach ($accounts as $account) {
            $api = $clientService->initialize($account);
            if (!$api) {
                continue;
            }

            try {
                $metaAcc = new MetaAccount('act_' . $account->meta_account_id);
                $params = [
                    'date_preset' => $this->datePreset,
                    'level' => 'adset',
                    'fields' => [
                        AdsInsightsFields::CAMPAIGN_ID,
                        AdsInsightsFields::ADSET_ID,
                        AdsInsightsFields::SPEND,
                        AdsInsightsFields::IMPRESSIONS,
                        AdsInsightsFields::CLICKS,
                        AdsInsightsFields::CTR,
                        AdsInsightsFields::CPC,
                        AdsInsightsFields::FREQUENCY,
                        AdsInsightsFields::ACTIONS,
                        AdsInsightsFields::ACTION_VALUES,
                        AdsInsightsFields::DATE_START,
                    ],
                ];

                $insights = $metaAcc->getInsights([], $params);

                foreach ($insights as $item) {
                    $data = $item->getData();
                    $date = $data['date_start'] ?? now()->toDateString();
                    $spend = (float) ($data['spend'] ?? 0);
                    $conversions = 0;
                    $conversionValue = 0.0;

                    if (!empty($data['actions'])) {
                        foreach ($data['actions'] as $action) {
                            if (in_array($action['action_type'], ['purchase', 'lead', 'offsite_conversion.fb_pixel_purchase'])) {
                                $conversions += (int) $action['value'];
                            }
                        }
                    }

                    if (!empty($data['action_values'])) {
                        foreach ($data['action_values'] as $val) {
                            if (in_array($val['action_type'], ['purchase', 'offsite_conversion.fb_pixel_purchase'])) {
                                $conversionValue += (float) $val['value'];
                            }
                        }
                    }

                    $roas = $spend > 0 ? ($conversionValue / $spend) : 0;
                    $cpa = $conversions > 0 ? ($spend / $conversions) : $spend;

                    AdInsightDaily::updateOrCreate(
                        [
                            'meta_entity_type' => 'ADSET',
                            'meta_entity_id' => $data['adset_id'],
                            'date' => $date,
                        ],
                        [
                            'spend' => $spend,
                            'impressions' => (int) ($data['impressions'] ?? 0),
                            'clicks' => (int) ($data['clicks'] ?? 0),
                            'ctr' => (float) ($data['ctr'] ?? 0),
                            'cpc' => (float) ($data['cpc'] ?? 0),
                            'frequency' => (float) ($data['frequency'] ?? 1.0),
                            'conversions' => $conversions,
                            'conversion_value' => $conversionValue,
                            'roas' => $roas,
                            'cpa' => $cpa,
                            'raw_metrics' => $data,
                        ]
                    );
                }
            } catch (\Throwable $e) {
                Log::error("Failed to sync insights for account {$account->id}: {$e->getMessage()}");
            }
        }
    }
}
