<?php

namespace App\Console\Commands;

use App\Jobs\SyncMetaInsightsJob;
use App\Models\AdAccount;
use App\Services\Meta\MetaAdsClientService;
use Illuminate\Console\Command;

class SyncMetaCommand extends Command
{
    protected $signature = 'meta:sync {--preset=today : Rentang waktu (today, yesterday, last_3d, last_7d, last_30d)}';
    protected $description = 'Sinkronisasi metrik performa insights dari Meta Marketing API';

    public function handle(MetaAdsClientService $clientService): int
    {
        $preset = $this->option('preset');
        $accounts = AdAccount::where('status', 'ACTIVE')->get();

        if ($accounts->isEmpty()) {
            $this->warn('Belum ada Ad Account aktif. Jalankan: php artisan meta:connect');
            return Command::FAILURE;
        }

        $this->info("Menjalankan sinkronisasi insights ({$preset}) untuk {$accounts->count()} akun...");

        $job = new SyncMetaInsightsJob(null, $preset);
        $job->handle($clientService);

        $this->info('✅ Sinkronisasi metrik performa selesai!');
        return Command::SUCCESS;
    }
}
