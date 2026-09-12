<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::post('/adset/{metaAdsetId}/status', [DashboardController::class, 'updateStatus'])->name('adset.status');
Route::post('/adset/{metaAdsetId}/scale', [DashboardController::class, 'scaleBudget'])->name('adset.scale');
Route::post('/automation/run-watchdog', [DashboardController::class, 'runWatchdogNow'])->name('automation.watchdog');
