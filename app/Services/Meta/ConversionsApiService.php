<?php

namespace App\Services\Meta;

use App\Models\AdAccount;
use FacebookAds\Api;
use FacebookAds\Object\ServerSide\ActionSource;
use FacebookAds\Object\ServerSide\CustomData;
use FacebookAds\Object\ServerSide\Event;
use FacebookAds\Object\ServerSide\EventRequest;
use FacebookAds\Object\ServerSide\UserData;
use Illuminate\Support\Facades\Log;

class ConversionsApiService
{
    public function __construct(protected MetaAdsClientService $clientService)
    {
    }

    /**
     * Kirim Server-side event ke Meta Conversions API (CAPI) dengan deduplikasi.
     */
    public function sendConversionEvent(
        AdAccount $account,
        string $pixelId,
        string $eventName,
        string $eventId,
        array $userDataArray = [],
        array $customDataArray = []
    ): array {
        $api = $this->clientService->initialize($account);

        $userData = (new UserData())
            ->setClientIpAddress($userDataArray['ip'] ?? request()->ip())
            ->setClientUserAgent($userDataArray['user_agent'] ?? request()->userAgent())
            ->setFbp($userDataArray['fbp'] ?? request()->cookie('_fbp'))
            ->setFbc($userDataArray['fbc'] ?? request()->cookie('_fbc'));

        if (!empty($userDataArray['email'])) {
            $userData->setEmail(hash('sha256', strtolower(trim($userDataArray['email']))));
        }

        if (!empty($userDataArray['phone'])) {
            $userData->setPhone(hash('sha256', preg_replace('/[^0-9]/', '', $userDataArray['phone'])));
        }

        $customData = (new CustomData())
            ->setValue($customDataArray['value'] ?? 0.0)
            ->setCurrency($customDataArray['currency'] ?? 'IDR');

        $event = (new Event())
            ->setEventName($eventName) // 'Purchase', 'Lead', 'AddToCart'
            ->setEventTime(time())
            ->setEventId($eventId) // Deduplication Key dengan Pixel di browser
            ->setEventSourceUrl($customDataArray['source_url'] ?? url()->current())
            ->setUserData($userData)
            ->setCustomData($customData)
            ->setActionSource(ActionSource::WEBSITE);

        $isSuccess = false;
        $errorMsg = null;

        if ($api) {
            try {
                $request = (new EventRequest($pixelId))->setEvents([$event]);
                $response = $request->execute();
                $isSuccess = true;
            } catch (\Throwable $e) {
                $errorMsg = $e->getMessage();
                Log::error("CAPI Event dispatch error: {$errorMsg}");
            }
        }

        return [
            'success' => $isSuccess || app()->environment('local'),
            'event_id' => $eventId,
            'event_name' => $eventName,
            'dispatched_to_meta' => $isSuccess,
            'error' => $errorMsg,
        ];
    }
}
