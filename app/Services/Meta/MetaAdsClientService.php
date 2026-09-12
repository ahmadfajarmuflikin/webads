<?php

namespace App\Services\Meta;

use App\Models\AdAccount;
use FacebookAds\Api;
use FacebookAds\Logger\CurlLogger;
use Illuminate\Support\Facades\Log;

class MetaAdsClientService
{
    protected ?Api $apiInstance = null;

    /**
     * Inisialisasi Meta API Client dengan token akun terkait.
     */
    public function initialize(AdAccount $account): ?Api
    {
        $token = $account->access_token ?: config('meta.default_access_token');
        $appId = config('meta.app_id');
        $appSecret = config('meta.app_secret');

        if (empty($token) || empty($appId) || empty($appSecret)) {
            Log::warning("Meta API credentials incomplete for account ID: {$account->id}");
            return null;
        }

        try {
            $api = Api::init($appId, $appSecret, $token);

            if (config('app.debug')) {
                $api->setLogger(new CurlLogger());
            }

            $this->apiInstance = $api;
            return $api;
        } catch (\Throwable $e) {
            Log::error("Failed to initialize Meta Ads API: {$e->getMessage()}");
            return null;
        }
    }

    public function getApi(): ?Api
    {
        return $this->apiInstance;
    }
}
