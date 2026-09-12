<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Meta App & Graph API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Facebook/Meta Marketing API v20.0+
    |
    */
    'app_id' => env('META_APP_ID', ''),
    'app_secret' => env('META_APP_SECRET', ''),
    'default_access_token' => env('META_DEFAULT_ACCESS_TOKEN', ''),
    'api_version' => env('META_API_VERSION', 'v20.0'),

    /*
    |--------------------------------------------------------------------------
    | AI Agent Security
    |--------------------------------------------------------------------------
    |
    | Secret API key for external agents (Hermes, OpenClaw, AutoGen)
    |
    */
    'agent_api_key' => env('AGENT_API_KEY', 'hermes_secret_meta_agent_key_2026'),

    /*
    |--------------------------------------------------------------------------
    | Performance Evaluation Benchmarks
    |--------------------------------------------------------------------------
    */
    'benchmarks' => [
        'min_evaluation_spend' => env('META_MIN_EVAL_SPEND', 50000), // Minimal spend sebelum menilai efektivitas
        'fatigue_frequency_threshold' => env('META_FATIGUE_FREQ', 2.8),
        'scale_roas_multiplier' => 1.25, // 125% dari target ROAS
        'kill_cpa_multiplier' => 1.50, // 150% dari target CPA tanpa penjualan
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook & Alerts
    |--------------------------------------------------------------------------
    */
    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN', ''),
        'chat_id' => env('TELEGRAM_CHAT_ID', ''),
    ],
];
