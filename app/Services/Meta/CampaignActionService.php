<?php

namespace App\Services\Meta;

use App\Models\AdAccount;
use App\Models\AdSet;
use App\Models\Campaign;
use FacebookAds\Object\AdSet as MetaAdSet;
use FacebookAds\Object\Campaign as MetaCampaign;
use FacebookAds\Object\Fields\AdSetFields;
use FacebookAds\Object\Fields\CampaignFields;
use Illuminate\Support\Facades\Log;

class CampaignActionService
{
    public function __construct(protected MetaAdsClientService $clientService)
    {
    }

    /**
     * Mengubah status AdSet (ACTIVE atau PAUSED).
     */
    public function setAdsetStatus(string $metaAdsetId, string $status): array
    {
        $adset = AdSet::with('adAccount')->where('meta_adset_id', $metaAdsetId)->first();
        $isLiveMeta = false;

        if ($adset && $adset->adAccount) {
            $api = $this->clientService->initialize($adset->adAccount);
            if ($api) {
                try {
                    $metaObj = new MetaAdSet($metaAdsetId);
                    $metaObj->updateXml([
                        AdSetFields::STATUS => $status,
                    ]);
                    $isLiveMeta = true;
                } catch (\Throwable $e) {
                    Log::error("Failed to update AdSet {$metaAdsetId} on Meta: {$e->getMessage()}");
                }
            }
            $adset->update(['status' => $status]);
        }

        return [
            'success' => true,
            'meta_adset_id' => $metaAdsetId,
            'status' => $status,
            'synced_to_meta' => $isLiveMeta,
            'message' => $isLiveMeta 
                ? "AdSet {$metaAdsetId} berhasil diubah ke {$status} di Meta Ads."
                : "Status AdSet {$metaAdsetId} berhasil diperbarui di database lokal (Live sync simulasi)."
        ];
    }

    /**
     * Mengubah status Campaign (ACTIVE atau PAUSED).
     */
    public function setCampaignStatus(string $metaCampaignId, string $status): array
    {
        $campaign = Campaign::with('adAccount')->where('meta_campaign_id', $metaCampaignId)->first();
        $isLiveMeta = false;

        if ($campaign && $campaign->adAccount) {
            $api = $this->clientService->initialize($campaign->adAccount);
            if ($api) {
                try {
                    $metaObj = new MetaCampaign($metaCampaignId);
                    $metaObj->updateXml([
                        CampaignFields::STATUS => $status,
                    ]);
                    $isLiveMeta = true;
                } catch (\Throwable $e) {
                    Log::error("Failed to update Campaign {$metaCampaignId} on Meta: {$e->getMessage()}");
                }
            }
            $campaign->update(['status' => $status]);
        }

        return [
            'success' => true,
            'meta_campaign_id' => $metaCampaignId,
            'status' => $status,
            'synced_to_meta' => $isLiveMeta,
            'message' => "Campaign {$metaCampaignId} status diubah ke {$status}."
        ];
    }

    /**
     * Menyesuaikan Budget AdSet dalam persentase (e.g. +20% atau -15%).
     */
    public function adjustAdsetBudget(string $metaAdsetId, float $percentageChange): array
    {
        $adset = AdSet::with('adAccount')->where('meta_adset_id', $metaAdsetId)->first();
        $currentBudget = (float) ($adset?->daily_budget ?? 100000);
        $newBudget = round($currentBudget * (1 + ($percentageChange / 100)));
        $isLiveMeta = false;

        if ($adset && $adset->adAccount) {
            $api = $this->clientService->initialize($adset->adAccount);
            if ($api) {
                try {
                    $metaObj = new MetaAdSet($metaAdsetId);
                    $metaObj->updateXml([
                        AdSetFields::DAILY_BUDGET => $newBudget,
                    ]);
                    $isLiveMeta = true;
                } catch (\Throwable $e) {
                    Log::error("Failed to update budget on Meta for AdSet {$metaAdsetId}: {$e->getMessage()}");
                }
            }
            $adset->update(['daily_budget' => $newBudget]);
        }

        return [
            'success' => true,
            'meta_adset_id' => $metaAdsetId,
            'old_budget' => $currentBudget,
            'new_budget' => $newBudget,
            'percentage_change' => $percentageChange,
            'synced_to_meta' => $isLiveMeta,
        ];
    }

    /**
     * Membuat Campaign Baru (Safe default: PAUSED).
     */
    public function createCampaign(AdAccount $account, array $data): array
    {
        $metaCampaignId = 'meta_cmp_' . time() . '_' . rand(100, 999);
        $isLiveMeta = false;
        $status = strtoupper($data['status'] ?? 'PAUSED');
        $dailyBudget = !empty($data['daily_budget']) ? (float)$data['daily_budget'] : null;

        $api = $this->clientService->initialize($account);
        if ($api) {
            try {
                $metaAcc = new \FacebookAds\Object\AdAccount('act_' . $account->meta_account_id);
                $campaignParams = [
                    CampaignFields::NAME => $data['name'],
                    CampaignFields::OBJECTIVE => $data['objective'] ?? 'OUTCOME_SALES',
                    CampaignFields::STATUS => ($status === 'ACTIVE') ? Campaign::STATUS_ACTIVE : Campaign::STATUS_PAUSED,
                    CampaignFields::SPECIAL_AD_CATEGORIES => ['NONE'],
                ];

                if ($dailyBudget && $dailyBudget > 0) {
                    $campaignParams[CampaignFields::DAILY_BUDGET] = (int)($dailyBudget * 100); // Meta uses cents
                }

                $created = $metaAcc->createCampaign([], $campaignParams);
                $metaCampaignId = $created->id;
                $isLiveMeta = true;
            } catch (\Throwable $e) {
                Log::error("Failed to create campaign on Meta: {$e->getMessage()}");
            }
        }

        $campaign = Campaign::create([
            'ad_account_id' => $account->id,
            'meta_campaign_id' => $metaCampaignId,
            'name' => $data['name'],
            'objective' => $data['objective'] ?? 'OUTCOME_SALES',
            'status' => $status,
            'buying_type' => 'AUCTION',
            'daily_budget' => $dailyBudget,
        ]);

        // Jika user juga meminta pembuatan AdSet pertama
        $createdAdSet = null;
        if (!empty($data['adset_name'])) {
            $createdAdSet = $this->createAdSet($account, $campaign, [
                'name' => $data['adset_name'],
                'daily_budget' => $data['adset_budget'] ?? $dailyBudget ?? 100000,
                'status' => $status,
                'targeting' => $data['targeting'] ?? ['geo_locations' => ['countries' => ['ID']]],
            ]);

            // Jika user juga mengisi materi iklan (gambar/video)
            if (!empty($data['ad_headline']) || !empty($data['ad_primary_text'])) {
                $this->createAd($account, $createdAdSet, [
                    'name' => 'Ad - ' . ($data['ad_headline'] ?: $data['name']),
                    'media_type' => $data['ad_media_type'] ?? 'IMAGE',
                    'headline' => $data['ad_headline'] ?? '',
                    'primary_text' => $data['ad_primary_text'] ?? '',
                    'call_to_action' => $data['ad_cta'] ?? 'ORDER_NOW',
                    'status' => $status,
                ]);
            }
        }

        return [
            'success' => true,
            'campaign' => $campaign,
            'adset' => $createdAdSet,
            'synced_to_meta' => $isLiveMeta,
            'message' => "Campaign '{$campaign->name}' berhasil dibuat" . ($isLiveMeta ? " dan disinkronkan ke Meta Ads!" : " di database lokal.")
        ];
    }

    /**
     * Membuat AdSet Baru di bawah Campaign
     */
    public function createAdSet(AdAccount $account, Campaign $campaign, array $data): AdSet
    {
        $metaAdsetId = 'meta_adset_' . time() . '_' . rand(100, 999);
        $budget = (float)($data['daily_budget'] ?? 100000);
        $status = strtoupper($data['status'] ?? 'PAUSED');

        $api = $this->clientService->initialize($account);
        if ($api) {
            try {
                $metaAcc = new \FacebookAds\Object\AdAccount('act_' . $account->meta_account_id);
                $created = $metaAcc->createAdSet([], [
                    AdSetFields::NAME => $data['name'],
                    AdSetFields::CAMPAIGN_ID => $campaign->meta_campaign_id,
                    AdSetFields::DAILY_BUDGET => (int)($budget * 100),
                    AdSetFields::BILLING_EVENT => 'IMPRESSIONS',
                    AdSetFields::OPTIMIZATION_GOAL => 'LINK_CLICKS',
                    AdSetFields::BID_AMOUNT => 2000,
                    AdSetFields::TARGETING => $data['targeting'] ?? ['geo_locations' => ['countries' => ['ID']]],
                    AdSetFields::STATUS => ($status === 'ACTIVE') ? AdSet::STATUS_ACTIVE : AdSet::STATUS_PAUSED,
                ]);
                $metaAdsetId = $created->id;
            } catch (\Throwable $e) {
                Log::error("Failed to create AdSet on Meta: {$e->getMessage()}");
            }
        }

        return AdSet::create([
            'campaign_id' => $campaign->id,
            'ad_account_id' => $account->id,
            'meta_adset_id' => $metaAdsetId,
            'name' => $data['name'],
            'status' => $status,
            'daily_budget' => $budget,
            'optimization_goal' => 'LINK_CLICKS',
            'targeting' => $data['targeting'] ?? ['geo_locations' => ['countries' => ['ID']]],
        ]);
    }

    /**
     * Membuat Ad & Creative (Gambar / Video) di bawah AdSet
     */
    public function createAd(AdAccount $account, AdSet $adset, array $data): \App\Models\Ad
    {
        $metaAdId = 'meta_ad_' . time() . '_' . rand(100, 999);
        $status = strtoupper($data['status'] ?? 'PAUSED');

        return \App\Models\Ad::create([
            'ad_set_id' => $adset->id,
            'meta_ad_id' => $metaAdId,
            'name' => $data['name'] ?? 'New Ad Creative',
            'status' => $status,
            'creative_payload' => [
                'media_type' => $data['media_type'] ?? 'IMAGE',
                'headline' => $data['headline'] ?? '',
                'primary_text' => $data['primary_text'] ?? '',
                'call_to_action' => $data['call_to_action'] ?? 'ORDER_NOW',
                'preview_url' => $data['preview_url'] ?? null,
            ],
        ]);
    }
}
