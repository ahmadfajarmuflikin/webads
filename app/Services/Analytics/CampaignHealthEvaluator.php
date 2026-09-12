<?php

namespace App\Services\Analytics;

use App\Models\AdSet;
use App\Models\AdInsightDaily;

class CampaignHealthEvaluator
{
    /**
     * Evaluasi efektivitas AdSet secara komprehensif termasuk analisis mendalam CTR.
     */
    public function evaluateAdset(AdSet $adset, int $days = 3): array
    {
        $adset->loadMissing('adAccount', 'campaign');
        $account = $adset->adAccount;

        $targetRoas = (float) ($account?->target_roas ?? 2.5);
        $targetCpa = (float) ($account?->target_cpa ?? 100000);

        // Ambil data insight harian sesuai rentang waktu
        $insights = AdInsightDaily::where('meta_entity_type', 'ADSET')
            ->where('meta_entity_id', $adset->meta_adset_id)
            ->where('date', '>=', now()->subDays($days)->toDateString())
            ->orderBy('date', 'asc')
            ->get();

        $totalSpend = (float) $insights->sum('spend');
        $totalConversions = (int) $insights->sum('conversions');
        $totalRevenue = (float) $insights->sum('conversion_value');
        $totalClicks = (int) $insights->sum('clicks');
        $totalImpressions = (int) $insights->sum('impressions');

        $avgFrequency = $insights->count() > 0 ? (float) $insights->avg('frequency') : 1.0;
        $avgCtr = $totalImpressions > 0 ? (($totalClicks / $totalImpressions) * 100) : 0.0;
        $avgCpc = $totalClicks > 0 ? ($totalSpend / $totalClicks) : 0.0;

        $cpa = $totalConversions > 0 ? ($totalSpend / $totalConversions) : $totalSpend;
        $roas = $totalSpend > 0 ? ($totalRevenue / $totalSpend) : 0.0;
        $cvr = $totalClicks > 0 ? (($totalConversions / $totalClicks) * 100) : 0.0; // Click-to-Conversion Rate

        // Ambil data historis 14 hari untuk deteksi Creative Decay Index
        $historicalInsights = AdInsightDaily::where('meta_entity_type', 'ADSET')
            ->where('meta_entity_id', $adset->meta_adset_id)
            ->where('date', '<', now()->subDays($days)->toDateString())
            ->where('date', '>=', now()->subDays(14)->toDateString())
            ->get();

        $historicalImpressions = (int) $historicalInsights->sum('impressions');
        $historicalClicks = (int) $historicalInsights->sum('clicks');
        $historicalCtr = $historicalImpressions > 0 ? (($historicalClicks / $historicalImpressions) * 100) : $avgCtr;

        $ctrDecayRatio = $historicalCtr > 0 ? ($avgCtr / $historicalCtr) : 1.0;

        // Analisis CTR & Hook Quality
        $ctrGrade = 'STANDAR';
        $ctrInsight = '';
        if ($avgCtr >= 2.2) {
            $ctrGrade = 'SANGAT TINGGI (WINNING HOOK)';
            $ctrInsight = 'Visual dan copy iklan sangat memikat audiens, rasio klik jauh di atas rata-rata industri.';
        } elseif ($avgCtr >= 1.2) {
            $ctrGrade = 'BAIK / STABIL';
            $ctrInsight = 'Daya tarik materi iklan cukup memadai dan stabil.';
        } else {
            $ctrGrade = 'LEMAH (WEAK HOOK)';
            $ctrInsight = 'Hanya sedikit audiens yang mengklik iklan. Thumbnail, judul, atau 3 detik pertama video kurang menarik.';
        }

        // Logika Klasifikasi Smart System
        $verdict = 'HEALTHY_STABLE';
        $healthScore = 75; // 0 - 100
        $reasons = [];
        $recommendedActions = [];
        $riskLevel = 'LOW';

        $minSpendThreshold = config('meta.benchmarks.min_evaluation_spend', 50000);

        // Kasus 1: Belum cukup data (Learning Phase)
        if ($totalSpend < $minSpendThreshold) {
            $verdict = 'LEARNING_PHASE';
            $healthScore = 60;
            $riskLevel = 'LOW';
            $reasons[] = "Total spend (Rp " . number_format($totalSpend, 0, ',', '.') . ") masih di bawah threshold minimum evaluasi. Biarkan data terkumpul.";
            $recommendedActions[] = 'MAINTAIN_OBSERVE';
        }
        // Kasus 2: Boncos / Burning Budget (Spend tinggi, 0 konversi atau CPA membengkak parah)
        elseif ($totalConversions === 0 && $totalSpend >= (1.5 * $targetCpa)) {
            $verdict = 'LOSING_MONEY';
            $healthScore = 15;
            $riskLevel = 'CRITICAL';
            $reasons[] = "AdSet boncos! Sudah menghabiskan Rp " . number_format($totalSpend, 0, ',', '.') . " tanpa ada konversi sama sekali (Target CPA: Rp " . number_format($targetCpa, 0, ',', '.') . ").";

            if ($avgCtr >= 1.8) {
                $reasons[] = "⚠️ Deteksi Bottleneck Landing Page: CTR iklan tinggi ({$avgCtr}%), artinya iklan memikat tetapi website gagal mengonversi pengunjung menjadi pembeli. Cek kecepatan web & penawaran harga.";
            }

            $recommendedActions[] = 'PAUSE_ADSET_IMMEDIATELY';
        }
        elseif ($totalConversions > 0 && $cpa >= (1.8 * $targetCpa)) {
            $verdict = 'LOSING_MONEY';
            $healthScore = 25;
            $riskLevel = 'HIGH';
            $reasons[] = "Biaya konversi (CPA) Rp " . number_format($cpa, 0, ',', '.') . " membengkak 80% melebihi target.";
            $recommendedActions[] = 'PAUSE_OR_REDUCE_BUDGET';
        }
        // Kasus 3: Winning Campaign / Siap Scale
        elseif ($roas >= ($targetRoas * 1.25) && $cpa <= ($targetCpa * 0.9) && $avgFrequency < 2.5) {
            $verdict = 'SCALING_CANDIDATE';
            $healthScore = 95;
            $riskLevel = 'LOW';
            $reasons[] = "Performa sangat prima! ROAS {$roas}x melampaui target ({$targetRoas}x) dan CPA hanya Rp " . number_format($cpa, 0, ',', '.') . " dengan audiens masih fresh (Frekuensi: " . round($avgFrequency, 2) . ").";
            $reasons[] = "Kualitas CTR: {$ctrGrade} ({$avgCtr}%). Rasio konversi klik ke pembelian (CVR) mencapai " . round($cvr, 2) . "%.";
            $recommendedActions[] = 'INCREASE_BUDGET_20_PERCENT';
            $recommendedActions[] = 'DUPLICATE_TO_NEW_LOOKALIKE';
        }
        // Kasus 4: Creative Fatigue (Iklan jenuh)
        elseif ($avgFrequency >= config('meta.benchmarks.fatigue_frequency_threshold', 2.8) || ($ctrDecayRatio < 0.70 && $avgCtr < 1.2)) {
            $verdict = 'CREATIVE_FATIGUE';
            $healthScore = 40;
            $riskLevel = 'MEDIUM';
            $reasons[] = "Materi iklan mengalami kejenuhan (Creative Fatigue). Frekuensi sudah mencapai " . round($avgFrequency, 2) . " dan CTR turun " . round((1 - $ctrDecayRatio) * 100, 1) . "% dibanding performa historis.";
            $reasons[] = "Saran: Audiens sudah bosan melihat gambar/video yang sama. Segera upload creative baru melalui Creative Studio.";
            $recommendedActions[] = 'REFRESH_AD_CREATIVE';
            $recommendedActions[] = 'EXPAND_AUDIENCE_SIZE';
        }
        // Kasus 5: Stabil dalam batas wajar
        else {
            $verdict = 'HEALTHY_STABLE';
            $healthScore = 78;
            $riskLevel = 'LOW';
            $reasons[] = "AdSet berjalan sesuai jalur metrik yang diharapkan. ROAS: {$roas}x (Target: {$targetRoas}x).";
            $reasons[] = "CTR: {$avgCtr}% ({$ctrGrade}).";
            $recommendedActions[] = 'MAINTAIN';
        }

        // Cek status Level Campaign induk
        $campaign = $adset->campaign;
        $campaignStatus = $campaign?->status ?? 'ACTIVE';
        $campaignName = $campaign?->name ?? 'Campaign';
        $isCampaignActive = ($campaignStatus === 'ACTIVE');

        // Effective status: Jika Campaign induk NONAKTIF, maka penayangan adset otomatis nonaktif (CAMPAIGN_PAUSED)
        $effectiveStatus = ($isCampaignActive && $adset->status === 'ACTIVE')
            ? 'ACTIVE'
            : (!$isCampaignActive ? 'CAMPAIGN_PAUSED' : 'PAUSED');

        if (!$isCampaignActive) {
            $verdict = 'CAMPAIGN_PAUSED';
            $healthScore = 30;
            $riskLevel = 'LOW';
            array_unshift($reasons, "⏸ Status Penayangan: NONAKTIF karena Campaign induk '{$campaignName}' berstatus {$campaignStatus}. Penayangan iklan otomatis terhenti.");
            $recommendedActions = ['ACTIVATE_CAMPAIGN_FIRST'];
        }

        return [
            'entity_type' => 'ADSET',
            'meta_id' => $adset->meta_adset_id,
            'name' => $adset->name,
            'status' => $effectiveStatus,
            'raw_adset_status' => $adset->status,
            'campaign_status' => $campaignStatus,
            'campaign_name' => $campaignName,
            'meta_campaign_id' => $campaign?->meta_campaign_id,
            'current_daily_budget' => (float) $adset->daily_budget,
            'timeframe_days' => $days,
            'verdict' => $verdict,
            'health_score' => $healthScore,
            'risk_level' => $riskLevel,
            'metrics' => [
                'spend' => $totalSpend,
                'revenue' => $totalRevenue,
                'conversions' => $totalConversions,
                'roas' => round($roas, 2),
                'target_roas' => $targetRoas,
                'cpa' => round($cpa, 2),
                'target_cpa' => $targetCpa,
                'ctr' => round($avgCtr, 3),
                'cpc' => round($avgCpc, 2),
                'cvr' => round($cvr, 2),
                'frequency' => round($avgFrequency, 2),
                'ctr_decay_ratio' => round($ctrDecayRatio, 2),
                'ctr_grade' => $ctrGrade,
                'ctr_insight' => $ctrInsight,
                'impressions' => $totalImpressions,
                'clicks' => $totalClicks,
            ],
            'reasons' => $reasons,
            'recommended_actions' => $recommendedActions,
        ];
    }

    /**
     * Evaluasi seluruh adset di bawah akun tertentu.
     */
    public function evaluateAllAdsets(int $adAccountId, int $days = 3): array
    {
        $adsets = AdSet::where('ad_account_id', $adAccountId)->get();
        $results = [];

        foreach ($adsets as $adset) {
            $results[] = $this->evaluateAdset($adset, $days);
        }

        return $results;
    }
}
