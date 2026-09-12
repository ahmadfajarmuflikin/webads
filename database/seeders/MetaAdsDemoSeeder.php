<?php

namespace Database\Seeders;

use App\Models\AdAccount;
use App\Models\AdInsightDaily;
use App\Models\AdSet;
use App\Models\AgentAuditLog;
use App\Models\Campaign;
use Illuminate\Database\Seeder;

class MetaAdsDemoSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat Demo Ad Account
        $account = AdAccount::updateOrCreate(
            ['meta_account_id' => 'act_8829102919'],
            [
                'name' => 'GlowSkin Official - Main Ads Account',
                'currency' => 'IDR',
                'timezone_name' => 'Asia/Jakarta',
                'access_token' => 'EAAGm0PX4124...demo_token',
                'target_roas' => 2.50,
                'target_cpa' => 120000.00,
                'status' => 'ACTIVE',
            ]
        );

        // 2. Campaign 1: Conversion Sales
        $campaign1 = Campaign::updateOrCreate(
            ['meta_campaign_id' => 'cmp_1200101'],
            [
                'ad_account_id' => $account->id,
                'name' => 'Q3 Serum Glow Booster - Conversion Sales',
                'objective' => 'OUTCOME_SALES',
                'status' => 'ACTIVE',
                'daily_budget' => 500000.00,
            ]
        );

        // AdSet 1: Winning Scale Candidate
        $adsetWinner = AdSet::updateOrCreate(
            ['meta_adset_id' => 'adset_winner_001'],
            [
                'campaign_id' => $campaign1->id,
                'ad_account_id' => $account->id,
                'name' => 'Broad Women 22-35 Beauty & Wellness [WINNING]',
                'status' => 'ACTIVE',
                'daily_budget' => 200000.00,
                'optimization_goal' => 'OFFSITE_CONVERSIONS',
            ]
        );

        // AdSet 2: Boncos (Burning spend with 0 conversions)
        $adsetBoncos = AdSet::updateOrCreate(
            ['meta_adset_id' => 'adset_boncos_002'],
            [
                'campaign_id' => $campaign1->id,
                'ad_account_id' => $account->id,
                'name' => 'Interest: Luxury Spa & Dermatology [BLEEDING]',
                'status' => 'ACTIVE',
                'daily_budget' => 150000.00,
                'optimization_goal' => 'OFFSITE_CONVERSIONS',
            ]
        );

        // AdSet 3: Creative Fatigue (High Frequency, plummeting CTR)
        $adsetFatigue = AdSet::updateOrCreate(
            ['meta_adset_id' => 'adset_fatigue_003'],
            [
                'campaign_id' => $campaign1->id,
                'ad_account_id' => $account->id,
                'name' => 'Retargeting 30 Days Website Visitors [FATIGUE]',
                'status' => 'ACTIVE',
                'daily_budget' => 100000.00,
                'optimization_goal' => 'OFFSITE_CONVERSIONS',
            ]
        );

        // Insights untuk Winner (3 hari terakhir: ROAS ~3.7x, CPA ~Rp 75.000)
        for ($i = 0; $i < 3; $i++) {
            $date = now()->subDays($i)->toDateString();
            AdInsightDaily::updateOrCreate(
                ['meta_entity_type' => 'ADSET', 'meta_entity_id' => 'adset_winner_001', 'date' => $date],
                [
                    'spend' => 180000,
                    'impressions' => 12500,
                    'clicks' => 320,
                    'cpc' => 562,
                    'ctr' => 2.56,
                    'frequency' => 1.45 + ($i * 0.1),
                    'conversions' => 3,
                    'conversion_value' => 690000,
                    'roas' => 3.83,
                    'cpa' => 60000,
                ]
            );
        }

        // Insights untuk Boncos (Spend Rp 280.000, Konversi: 0) -> Kill switch trigger
        for ($i = 0; $i < 2; $i++) {
            $date = now()->subDays($i)->toDateString();
            AdInsightDaily::updateOrCreate(
                ['meta_entity_type' => 'ADSET', 'meta_entity_id' => 'adset_boncos_002', 'date' => $date],
                [
                    'spend' => 140000,
                    'impressions' => 8400,
                    'clicks' => 65,
                    'cpc' => 2153,
                    'ctr' => 0.77,
                    'frequency' => 1.2,
                    'conversions' => 0,
                    'conversion_value' => 0,
                    'roas' => 0,
                    'cpa' => 140000,
                ]
            );
        }

        // Insights untuk Fatigue (Frequency 3.2, CTR anjlok ke 0.6%)
        for ($i = 0; $i < 3; $i++) {
            $date = now()->subDays($i)->toDateString();
            AdInsightDaily::updateOrCreate(
                ['meta_entity_type' => 'ADSET', 'meta_entity_id' => 'adset_fatigue_003', 'date' => $date],
                [
                    'spend' => 95000,
                    'impressions' => 6100,
                    'clicks' => 38,
                    'cpc' => 2500,
                    'ctr' => 0.62,
                    'frequency' => 3.15 + ($i * 0.1),
                    'conversions' => 1,
                    'conversion_value' => 150000,
                    'roas' => 1.57,
                    'cpa' => 95000,
                ]
            );
        }

        // Data historis lama (10 hari lalu) untuk adset fatigue agar decay index terhitung
        AdInsightDaily::updateOrCreate(
            ['meta_entity_type' => 'ADSET', 'meta_entity_id' => 'adset_fatigue_003', 'date' => now()->subDays(10)->toDateString()],
            [
                'spend' => 100000,
                'impressions' => 7000,
                'clicks' => 160,
                'cpc' => 625,
                'ctr' => 2.28,
                'frequency' => 1.3,
                'conversions' => 4,
                'conversion_value' => 600000,
                'roas' => 6.0,
                'cpa' => 25000,
            ]
        );

        // Audit Log Simulasi Agent
        AgentAuditLog::create([
            'agent_id' => 'openclaw_optimizer',
            'action' => 'INCREASE_BUDGET',
            'target_type' => 'ADSET',
            'target_id' => 'adset_winner_001',
            'reason' => 'Evaluated ROAS 3.8x (Target 2.5x) and CPA Rp 60.000. Scaled daily budget by 15%.',
            'payload' => ['old_budget' => 170000, 'new_budget' => 200000],
        ]);
    }
}
