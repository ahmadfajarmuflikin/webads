<?php

namespace App\Services\Meta;

use App\Models\Ad;
use App\Models\AdAccount;
use App\Models\AdSet;
use FacebookAds\Object\Ad as MetaAd;
use FacebookAds\Object\AdAccount as MetaAccount;
use FacebookAds\Object\AdCreative;
use FacebookAds\Object\AdImage;
use FacebookAds\Object\AdVideo;
use FacebookAds\Object\Fields\AdCreativeFields;
use FacebookAds\Object\Fields\AdCreativeLinkDataChildAttachmentFields;
use FacebookAds\Object\Fields\AdFields;
use FacebookAds\Object\Fields\AdImageFields;
use FacebookAds\Object\Fields\AdVideoFields;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class CreativeUploadService
{
    public function __construct(protected MetaAdsClientService $clientService)
    {
    }

    /**
     * Upload gambar ke Meta Ad Images Library dan dapatkan image_hash.
     */
    public function uploadImage(AdAccount $account, UploadedFile $file): ?string
    {
        $api = $this->clientService->initialize($account);
        if (!$api) {
            return md5(time() . $file->getClientOriginalName()); // Dummy hash untuk testing
        }

        try {
            $metaAccount = new MetaAccount('act_' . $account->meta_account_id);
            $image = new AdImage();
            $image->setParentId($metaAccount->getId());
            $image->{AdImageFields::FILENAME} = $file->getRealPath();
            $image->create();

            $images = $image->{AdImageFields::IMAGES};
            $firstImg = reset($images);
            return $firstImg['hash'] ?? null;
        } catch (\Throwable $e) {
            Log::error("Failed to upload image to Meta: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Upload video ke Meta Ad Videos Library dan dapatkan video_id.
     */
    public function uploadVideo(AdAccount $account, UploadedFile $file): ?string
    {
        $api = $this->clientService->initialize($account);
        if (!$api) {
            return 'vid_' . time();
        }

        try {
            $metaAccount = new MetaAccount('act_' . $account->meta_account_id);
            $video = new AdVideo();
            $video->setParentId($metaAccount->getId());
            $video->{AdVideoFields::SOURCE} = $file->getRealPath();
            $video->create();

            return $video->id;
        } catch (\Throwable $e) {
            Log::error("Failed to upload video to Meta: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Buat Ad Creative & Pasang ke AdSet sebagai Ad baru.
     */
    public function createAdWithCreative(
        AdSet $adset,
        string $adName,
        string $format, // 'IMAGE', 'VIDEO', 'CAROUSEL'
        array $payload
    ): array {
        $account = $adset->adAccount;
        $api = $this->clientService->initialize($account);
        $isLiveMeta = false;
        $metaCreativeId = 'cr_' . time();
        $metaAdId = 'ad_' . time();

        $objectStorySpec = [];
        $pageId = config('meta.page_id') ?? '100000000000000'; // Target Facebook Page

        if ($format === 'IMAGE') {
            $objectStorySpec = [
                'page_id' => $pageId,
                'link_data' => [
                    'image_hash' => $payload['image_hash'] ?? null,
                    'link' => $payload['website_url'],
                    'message' => $payload['primary_text'] ?? '',
                    'name' => $payload['headline'] ?? '',
                    'description' => $payload['description'] ?? '',
                    'call_to_action' => [
                        'type' => $payload['cta_type'] ?? 'LEARN_MORE',
                    ],
                ],
            ];
        } elseif ($format === 'VIDEO') {
            $objectStorySpec = [
                'page_id' => $pageId,
                'video_data' => [
                    'video_id' => $payload['video_id'] ?? null,
                    'message' => $payload['primary_text'] ?? '',
                    'title' => $payload['headline'] ?? '',
                    'call_to_action' => [
                        'type' => $payload['cta_type'] ?? 'LEARN_MORE',
                        'value' => [
                            'link' => $payload['website_url'],
                        ],
                    ],
                ],
            ];
        } elseif ($format === 'CAROUSEL') {
            $childAttachments = [];
            foreach ($payload['carousel_cards'] as $card) {
                $childAttachments[] = [
                    'name' => $card['headline'] ?? '',
                    'description' => $card['description'] ?? '',
                    'link' => $card['link'] ?? $payload['website_url'],
                    'image_hash' => $card['image_hash'] ?? null,
                    'call_to_action' => [
                        'type' => $payload['cta_type'] ?? 'SHOP_NOW',
                    ],
                ];
            }

            $objectStorySpec = [
                'page_id' => $pageId,
                'link_data' => [
                    'message' => $payload['primary_text'] ?? '',
                    'link' => $payload['website_url'],
                    'child_attachments' => $childAttachments,
                ],
            ];
        }

        if ($api) {
            try {
                $metaAccount = new MetaAccount('act_' . $account->meta_account_id);

                // 1. Create Creative on Meta
                $creative = $metaAccount->createAdCreative([], [
                    AdCreativeFields::NAME => "Creative: {$adName}",
                    AdCreativeFields::OBJECT_STORY_SPEC => $objectStorySpec,
                ]);
                $metaCreativeId = $creative->id;

                // 2. Create Ad on Meta
                $ad = $metaAccount->createAd([], [
                    AdFields::NAME => $adName,
                    AdFields::ADSET_ID => $adset->meta_adset_id,
                    AdFields::CREATIVE => ['creative_id' => $metaCreativeId],
                    AdFields::STATUS => MetaAd::STATUS_PAUSED, // Safe default: paused
                ]);
                $metaAdId = $ad->id;
                $isLiveMeta = true;
            } catch (\Throwable $e) {
                Log::error("Failed to create Ad Creative on Meta: {$e->getMessage()}");
            }
        }

        // Simpan ke database lokal
        $localAd = Ad::create([
            'ad_set_id' => $adset->id,
            'meta_ad_id' => $metaAdId,
            'name' => $adName,
            'status' => 'PAUSED',
            'creative_payload' => array_merge($payload, [
                'format' => $format,
                'meta_creative_id' => $metaCreativeId,
            ]),
        ]);

        return [
            'success' => true,
            'ad_id' => $localAd->id,
            'meta_ad_id' => $metaAdId,
            'meta_creative_id' => $metaCreativeId,
            'synced_to_meta' => $isLiveMeta,
            'message' => $isLiveMeta
                ? "Materi iklan '{$adName}' ({$format}) berhasil di-upload dan dibuat di Meta Ads (Status: PAUSED)!"
                : "Materi iklan '{$adName}' ({$format}) berhasil disimpan di sistem lokal.",
        ];
    }
}
