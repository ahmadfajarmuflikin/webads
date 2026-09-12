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

        $api = $this->clientService->initialize($account);
        if ($api) {
            try {
                $metaAcc = new \FacebookAds\Object\AdAccount('act_' . $account->meta_account_id);
                $created = $metaAcc->createCampaign([], [
                    CampaignFields::NAME => $data['name'],
                    CampaignFields::OBJECTIVE => $data['objective'] ?? 'OUTCOME_SALES',
                    CampaignFields::STATUS => Campaign::STATUS_PAUSED,
                    CampaignFields::SPECIAL_AD_CATEGORIES => ['NONE'],
                ]);
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
            'status' => 'PAUSED',
            'buying_type' => 'AUCTION',
            'daily_budget' => $data['daily_budget'] ?? null,
        ]);

        return [
            'success' => true,
            'campaign' => $campaign,
            'synced_to_meta' => $isLiveMeta,
            'message' => "Campaign '{$campaign->name}' berhasil dibuat dengan status awal PAUSED."
        ];
    }
}
