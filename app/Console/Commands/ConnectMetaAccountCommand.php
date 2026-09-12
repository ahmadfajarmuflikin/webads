<?php

namespace App\Console\Commands;

use App\Models\AdAccount;
use App\Models\AdSet;
use App\Models\Campaign;
use App\Services\Meta\MetaAdsClientService;
use FacebookAds\Object\AdAccount as MetaAccount;
use FacebookAds\Object\Fields\AdAccountFields;
use FacebookAds\Object\Fields\CampaignFields;
use FacebookAds\Object\Fields\AdSetFields;
use Illuminate\Console\Command;

class ConnectMetaAccountCommand extends Command
{
    protected $signature = 'meta:connect 
                            {--account-id= : Meta Ad Account ID (contoh: act_123456789)}
                            {--token= : Meta Access Token (System User atau Long-lived Token)}
                            {--target-roas=2.5 : Target ROAS benchmark}
                            {--target-cpa=100000 : Target CPA benchmark dalam IDR}';

    protected $description = 'Hubungkan akun Meta Ads baru dan verifikasi koneksi Graph API';

    public function handle(MetaAdsClientService $clientService): int
    {
        $this->info('=============================================');
        $this->info('  🔗 KONEKSI META MARKETING API (v20.0+)     ');
        $this->info('=============================================');

        $rawAccountId = $this->option('account-id') ?: $this->ask('Masukkan Meta Ad Account ID (contoh: act_123456789)');
        $metaAccountId = preg_replace('/^act_/', '', trim($rawAccountId));

        $token = $this->option('token') ?: $this->secret('Masukkan Meta Access Token');
        $targetRoas = (float) ($this->option('target-roas') ?: $this->ask('Target ROAS akun', '2.5'));
        $targetCpa = (float) ($this->option('target-cpa') ?: $this->ask('Target CPA akun (IDR)', '100000'));

        $this->info("\nMemverifikasi koneksi ke Meta Graph API...");

        // Simpan atau update AdAccount sementara
        $account = AdAccount::updateOrCreate(
            ['meta_account_id' => $metaAccountId],
            [
                'name' => "Meta Account act_{$metaAccountId}",
                'access_token' => $token,
                'target_roas' => $targetRoas,
                'target_cpa' => $targetCpa,
                'status' => 'ACTIVE',
            ]
        );

        $api = $clientService->initialize($account);

        if (!$api) {
            $this->warn("⚠️  Catatan: META_APP_ID dan META_APP_SECRET di file .env belum diisi.");
            $this->warn("   Akun tetap tersimpan di database lokal untuk mode simulasi.");
            return Command::SUCCESS;
        }

        try {
            $metaAccount = new MetaAccount('act_' . $metaAccountId);
            $accountData = $metaAccount->read([
                AdAccountFields::NAME,
                AdAccountFields::CURRENCY,
                AdAccountFields::TIMEZONE_NAME,
                AdAccountFields::ACCOUNT_STATUS,
            ]);

            $account->update([
                'name' => $accountData->{AdAccountFields::NAME} ?? $account->name,
                'currency' => $accountData->{AdAccountFields::CURRENCY} ?? 'IDR',
                'timezone_name' => $accountData->{AdAccountFields::TIMEZONE_NAME} ?? 'Asia/Jakarta',
            ]);

            $this->info("✅ Terhubung Berhasil!");
            $this->line("   • Nama Akun: " . $account->name);
            $this->line("   • Mata Uang:  " . $account->currency);
            $this->line("   • Timezone:   " . $account->timezone_name);

            // Fetch live campaigns
            $this->info("\nMengambil daftar campaign aktif dari Meta...");
            $campaigns = $metaAccount->getCampaigns([
                CampaignFields::ID,
                CampaignFields::NAME,
                CampaignFields::OBJECTIVE,
                CampaignFields::STATUS,
                CampaignFields::DAILY_BUDGET,
            ]);

            $campaignCount = 0;
            foreach ($campaigns as $camp) {
                $cData = $camp->getData();
                Campaign::updateOrCreate(
                    ['meta_campaign_id' => $cData['id']],
                    [
                        'ad_account_id' => $account->id,
                        'name' => $cData['name'] ?? 'Unnamed Campaign',
                        'objective' => $cData['objective'] ?? 'OUTCOME_SALES',
                        'status' => $cData['status'] ?? 'ACTIVE',
                        'daily_budget' => isset($cData['daily_budget']) ? ($cData['daily_budget'] / 100) : null,
                    ]
                );
                $campaignCount++;
            }

            $this->info("✅ Sukses mengimpor {$campaignCount} campaign!");
            $this->line("\nSekarang Anda dapat menjalankan sinkronisasi metrik kapan saja dengan:");
            $this->line("👉 <comment>php artisan meta:sync</comment>");

            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("❌ Gagal terhubung ke Meta: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
