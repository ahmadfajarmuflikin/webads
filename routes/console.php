<?php

use App\Jobs\RunAutomationRulesJob;
use App\Jobs\SyncMetaInsightsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Background Workers
|--------------------------------------------------------------------------
*/

// Sinkronisasi data performa insights dari Meta Marketing API setiap 30 menit
Schedule::job(new SyncMetaInsightsJob(null, 'today'))->everyThirtyMinutes();

// Smart Automation Watchdog: Evaluasi Kill-Switch & Auto-Scale setiap 15 menit
Schedule::job(new RunAutomationRulesJob)->everyFifteenMinutes();
