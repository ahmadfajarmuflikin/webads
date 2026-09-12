<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MetaOAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::post('/adset/{metaAdsetId}/status', [DashboardController::class, 'updateStatus'])->name('adset.status');
Route::post('/adset/{metaAdsetId}/scale', [DashboardController::class, 'scaleBudget'])->name('adset.scale');
Route::post('/automation/run-watchdog', [DashboardController::class, 'runWatchdogNow'])->name('automation.watchdog');
Route::post('/account/connect-manual', [DashboardController::class, 'connectManual'])->name('account.connect_manual');
Route::post('/meta/sync', [DashboardController::class, 'syncData'])->name('meta.sync_web');
Route::post('/campaign/create', [DashboardController::class, 'createCampaign'])->name('campaign.create');
Route::post('/creative/upload', [DashboardController::class, 'uploadCreative'])->name('creative.upload');

/*
|--------------------------------------------------------------------------
| Meta Facebook OAuth 2.0 Flow
|--------------------------------------------------------------------------
*/
Route::get('/auth/meta/redirect', [MetaOAuthController::class, 'redirect'])->name('auth.meta.redirect');
Route::get('/auth/meta/callback', [MetaOAuthController::class, 'callback'])->name('auth.meta.callback');
