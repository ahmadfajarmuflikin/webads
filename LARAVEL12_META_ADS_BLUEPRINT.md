# 🚀 Blueprint & Arsitektur WebApp Laravel 12: Smart Meta Ads Manager & Agentic Co-Pilot

Dokumen ini merinci arsitektur teknis, skema basis data, algoritma penilaian efektivitas (Smart System), integrasi AI Agent (**Hermes**, **OpenClaw**, MCP/Tool-calling), serta sistem otomasi eksekusi Meta Ads (Facebook & Instagram) berbasis **Laravel 12**.

---

## 📑 Daftar Isi
1. [Tech Stack & Arsitektur Sistem](#1-tech-stack--arsitektur-sistem)
2. [Skema Basis Data (Database Schema)](#2-skema-basis-data-database-schema)
3. [Integrasi Meta Marketing API v20.0+](#3-integrasi-meta-marketing-api-v200)
4. [Smart Evaluation Engine (Sistem Penentu Efektivitas Ads)](#4-smart-evaluation-engine-sistem-penentu-efektivitas-ads)
5. [Integrasi AI Agent (OpenClaw & Hermes Tool Protocol)](#5-integrasi-ai-agent-openclaw--hermes-tool-protocol)
6. [Campaign Management & Execution Engine](#6-campaign-management--execution-engine)
7. [Fitur-Fitur Lanjutan untuk Melipatgandakan ROI Ads](#7-fitur-fitur-lanjutan-untuk-melipatgandakan-roi-ads)
8. [Panduan Langkah Implementasi (Step-by-Step Laravel 12)](#8-panduan-langkah-implementasi-step-by-step-laravel-12)

---

## 1. Tech Stack & Arsitektur Sistem

```
                      +------------------------------------------+
                      |           AI Agent Ecosystem             |
                      |   (Hermes Agent, OpenClaw, AutoGen)      |
                      +--------------------+---------------------+
                                           |  JSON-RPC / REST API
                                           |  (Bearer Auth + Webhooks)
                                           v
+------------------+     +---------------------------------------+
|  User / Marketer |<--->|           Laravel 12 App              |
|  (Inertia /      |     |  - Meta Graph API Service             |
|   Filament 4)    |     |  - Smart Evaluation Engine            |
+------------------+     |  - Rule Automations (Kill/Scale)      |
                         |  - Tool Endpoints for Agents          |
                         +-------------------+-------------------+
                                             |
             +-------------------------------+-------------------------------+
             |                               |                               |
             v                               v                               v
    +-----------------+             +-----------------+             +-----------------+
    | PostgreSQL 16   |             | Redis + Horizon |             | Meta Graph API  |
    | (TimescaleDB /  |             | (Jobs, Syncing, |             | (v20.0+)        |
    |  JSONB support) |             |  Rate Limiting) |             | Marketing SDK   |
    +-----------------+             +-----------------+             +-----------------+
```

- **Framework**: Laravel 12 (PHP 8.3+)
- **Database**: PostgreSQL 16 (atau MySQL 8.0) dengan dukungan kolom JSONB untuk payload raw Meta Insights.
- **Queue/Worker**: Laravel Horizon + Redis untuk sinkronisasi data insights dan eksekusi automation rules.
- **SDK**: `facebook/php-business-sdk` (Facebook Marketing API).
- **Agent Integration**: Standard Tool-Use Schema (OpenAI/Anthropic compatible function calling), Webhook Callbacks, serta endpoint OpenAPI 3.1.
- **Frontend Dashboard (Opsional)**: Laravel Filament v4 / Inertia.js + Tailwind CSS.

---

## 2. Skema Basis Data (Database Schema)

### 2.1. Tabel Inti

#### `ad_accounts`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | `ulid` / `bigIncrements` | Primary Key |
| `meta_account_id` | `string` (Unique) | ID akun Meta (e.g. `act_123456789`) |
| `name` | `string` | Nama Akun Iklan |
| `currency` | `string(3)` | IDR, USD, dll. |
| `timezone` | `string` | Asia/Jakarta |
| `access_token` | `text` (Encrypted) | System User Token atau Long-lived Token |
| `target_roas` | `decimal(5,2)` | Benchmark target ROAS akun |
| `target_cpa` | `decimal(15,2)` | Benchmark target Cost Per Acquisition |
| `status` | `string` | ACTIVE, DISABLED, UNSETTLED |

#### `campaigns`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | `ulid` / `bigIncrements` | Primary Key |
| `ad_account_id` | `foreignId` | Relasi ke `ad_accounts` |
| `meta_campaign_id`| `string` (Unique) | ID Campaign di Meta |
| `name` | `string` | Nama Campaign |
| `objective` | `string` | OUTCOME_SALES, OUTCOME_LEADS, dll. |
| `status` | `string` | ACTIVE, PAUSED, ARCHIVED, DELETED |
| `buying_type` | `string` | AUCTION |
| `daily_budget` | `decimal(15,2)` | Budget harian (jika CBO/Advantage Campaign Budget) |
| `lifetime_budget`| `decimal(15,2)` | Budget lifetime |
| `bid_strategy` | `string` | LOWEST_COST_WITHOUT_CAP, COST_CAP |

#### `ad_sets`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | `ulid` | Primary Key |
| `campaign_id` | `foreignId` | Relasi ke `campaigns` |
| `meta_adset_id` | `string` (Unique) | ID AdSet di Meta |
| `name` | `string` | Nama AdSet |
| `status` | `string` | ACTIVE, PAUSED, ARCHIVED |
| `targeting` | `jsonb` | Umur, gender, interest, custom audiences |
| `daily_budget` | `decimal(15,2)` | Budget jika ABO (Ad Set Budget Optimization) |
| `optimization_goal`| `string` | PURCHASE, LEAD, LINK_CLICKS |

#### `ads` & `ad_creatives`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | `ulid` | Primary Key |
| `ad_set_id` | `foreignId` | Relasi ke `ad_sets` |
| `meta_ad_id` | `string` (Unique) | ID Ad di Meta |
| `name` | `string` | Nama Ad |
| `status` | `string` | ACTIVE, PAUSED, DISAPPROVED |
| `creative_payload`| `jsonb` | Headline, Primary Text, Image/Video URL, Call to Action |

#### `ad_insights_daily` (Partitioned by date disarankan)
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | `bigIncrements` | Primary Key |
| `meta_entity_type`| `enum` | CAMPAIGN, ADSET, AD |
| `meta_entity_id` | `string` | ID Entity terkait |
| `date` | `date` | Tanggal data |
| `spend` | `decimal(15,2)` | Total pengeluaran |
| `impressions` | `bigInteger` | Tayangan |
| `clicks` | `integer` | Total Klik |
| `cpc` | `decimal(10,2)` | Cost Per Click |
| `ctr` | `decimal(6,3)` | Click-through Rate (%) |
| `frequency` | `decimal(5,2)` | Rata-rata frekuensi tayang |
| `conversions` | `integer` | Purchase / Lead count |
| `conversion_value`| `decimal(15,2)`| Revenue dari Ads |
| `roas` | `decimal(6,2)` | Return on Ad Spend (`conversion_value / spend`) |
| `cpa` | `decimal(15,2)` | Cost Per Acquisition (`spend / conversions`) |
| `raw_metrics` | `jsonb` | Payload utuh dari Meta (video watch time, add to cart, dll.) |

#### `automation_rules` & `audit_logs`
| Kolom | Tipe | Keterangan |
|---|---|---|
| `id` | `ulid` | Primary Key |
| `ad_account_id` | `foreignId` | Akun terkait |
| `rule_name` | `string` | e.g. "Stop AdSet jika Spend > 2x Target CPA tanpa konversi" |
| `condition` | `jsonb` | Format rule logika (IF spend > 150000 AND purchases == 0) |
| `action` | `string` | PAUSE_ADSET, SCALE_BUDGET_20, SEND_ALERT |
| `is_active` | `boolean` | Status on/off |

---

## 3. Integrasi Meta Marketing API v20.0+

### 3.1. Instalasi SDK
```bash
composer require facebook/php-business-sdk
```

### 3.2. Service Client Wrapper (`MetaAdsClientService.php`)
Menangani inisialisasi API, multi-account token rotation, serta handling rate limit header `x-business-use-case-usage`.

```php
namespace App\Services\Meta;

use FacebookAds\Api;
use FacebookAds\Logger\CurlLogger;
use App\Models\AdAccount;

class MetaAdsClientService
{
    public function initialize(AdAccount $account): Api
    {
        $api = Api::init(
            config('services.meta.app_id'),
            config('services.meta.app_secret'),
            $account->access_token
        );

        if (config('app.debug')) {
            $api->setLogger(new CurlLogger());
        }

        return $api;
    }
}
```

### 3.3. Sync Job Worker: Mengambil Real-time Insights
Gunakan Job `SyncMetaInsightsJob` yang dijadwalkan setiap 15 menit atau 1 jam via Laravel Scheduler (`routes/console.php`).

```php
use FacebookAds\Object\AdAccount as MetaAccount;
use FacebookAds\Object\Fields\AdsInsightsFields;

public function syncAccountInsights(AdAccount $account, string $datePreset = 'today')
{
    $this->metaClient->initialize($account);
    $metaAccount = new MetaAccount('act_' . $account->meta_account_id);

    $params = [
        'date_preset' => $datePreset,
        'level' => 'adset',
        'fields' => [
            AdsInsightsFields::CAMPAIGN_ID,
            AdsInsightsFields::ADSET_ID,
            AdsInsightsFields::SPEND,
            AdsInsightsFields::IMPRESSIONS,
            AdsInsightsFields::CLICKS,
            AdsInsightsFields::CTR,
            AdsInsightsFields::CPC,
            AdsInsightsFields::FREQUENCY,
            AdsInsightsFields::ACTIONS,
            AdsInsightsFields::ACTION_VALUES,
        ],
    ];

    $insights = $metaAccount->getInsights([], $params);
    // Parse dan upsert ke tabel ad_insights_daily
}
```

---

## 4. Smart Evaluation Engine (Sistem Penentu Efektivitas Ads)

Sistem evaluasi cerdas menggabungkan **3 Lapisan Analisis**:
1. **Financial Health (ROAS & CPA vs Target)**
2. **Funnel Quality (CTR, CPC, Outbound Click Rate, Add-to-Cart Ratio)**
3. **Fatigue & Audience Saturation (Frequency vs CTR Trend, First Impression Ratio)**

### 4.1. Health Scoring Matrix & Status Tag

| Status Tag | Kriteria Deteksi | Rekomendasi Tindakan Otomatis |
|---|---|---|
| 🟢 **`SCALING_CANDIDATE`** | ROAS $\ge 1.3 \times$ Target, CPA $\le 0.8 \times$ Target, Freq $< 2.2$, Spend $> 80\%$ budget | Naikkan budget 15% - 20% bertahap |
| 🟡 **`HEALTHY_STABLE`** | ROAS & CPA berada di margin $\pm 10\%$ dari target | Pertahankan, pantau tren harian |
| 🟠 **`CREATIVE_FATIGUE`** | Frequency $> 2.8$, CTR turun $> 30\%$ dalam 3 hari terakhir, CPA naik | Suntikkan materi iklan (creative) baru |
| 🔴 **`LOSING_MONEY`** | Spend $\ge 1.5 \times$ Target CPA namun Konversi $= 0$, atau ROAS $< 0.6 \times$ Target | **Kill Switch**: Pause adset segera |
| ⚪ **`LEARNING_PHASE`** | Konversi belum mencapai 50 event per minggu | Hindari perubahan budget drastis ($>20\%$) |

### 4.2. Implementation Service: `CampaignHealthEvaluator.php`

```php
namespace App\Services\Analytics;

use App\Models\AdSet;
use App\Models\AdInsightDaily;

class CampaignHealthEvaluator
{
    public function evaluate(AdSet $adset, int $days = 3): array
    {
        $insights = AdInsightDaily::where('meta_entity_id', $adset->meta_adset_id)
            ->where('date', '>=', now()->subDays($days))
            ->get();

        $totalSpend = $insights->sum('spend');
        $totalConversions = $insights->sum('conversions');
        $totalRevenue = $insights->sum('conversion_value');
        $avgFrequency = $insights->avg('frequency');
        $avgCtr = $insights->avg('ctr');

        $cpa = $totalConversions > 0 ? ($totalSpend / $totalConversions) : $totalSpend;
        $roas = $totalSpend > 0 ? ($totalRevenue / $totalSpend) : 0;

        $targetRoas = $adset->adAccount->target_roas ?? 2.5;
        $targetCpa = $adset->adAccount->target_cpa ?? 100000; // IDR

        $verdict = 'HEALTHY_STABLE';
        $score = 70; // 0 - 100
        $reasons = [];
        $actions = [];

        // 1. Cek Kebocoran Anggaran (Bleeding)
        if ($totalSpend >= (1.5 * $targetCpa) && $totalConversions === 0) {
            $verdict = 'LOSING_MONEY';
            $score = 15;
            $reasons[] = "Pengeluaran sudah mencapai {$totalSpend} tanpa ada konversi (Target CPA: {$targetCpa}).";
            $actions[] = 'PAUSE_ADSET';
        }
        // 2. Cek Potensi Scale
        elseif ($roas >= ($targetRoas * 1.25) && $cpa <= ($targetCpa * 0.85) && $avgFrequency < 2.5) {
            $verdict = 'SCALING_CANDIDATE';
            $score = 95;
            $reasons[] = "ROAS sangat tinggi ({$roas}x) dan CPA rendah dengan frekuensi audiens masih segar ({$avgFrequency}).";
            $actions[] = 'INCREASE_BUDGET_20_PERCENT';
        }
        // 3. Cek Kelelahan Kreatif (Creative Fatigue)
        elseif ($avgFrequency >= 2.8 && $avgCtr < 1.0) {
            $verdict = 'CREATIVE_FATIGUE';
            $score = 45;
            $reasons[] = "Audiens jenuh dengan iklan. Frekuensi tinggi ({$avgFrequency}) dan CTR rendah ({$avgCtr}%).";
            $actions[] = 'REFRESH_CREATIVE';
        }

        return [
            'adset_id' => $adset->meta_adset_id,
            'adset_name' => $adset->name,
            'verdict' => $verdict,
            'score' => $score,
            'metrics' => [
                'spend' => $totalSpend,
                'conversions' => $totalConversions,
                'cpa' => round($cpa, 2),
                'roas' => round($roas, 2),
                'frequency' => round($avgFrequency, 2),
                'ctr' => round($avgCtr, 2),
            ],
            'reasons' => $reasons,
            'recommended_actions' => $actions,
        ];
    }
}
```

---

## 5. Integrasi AI Agent (OpenClaw & Hermes Tool Protocol)

Agar AI agent seperti **Hermes** atau **OpenClaw** dapat berinteraksi dua arah (membaca performa dan mengeksekusi aksi), Laravel menyediakan:
1. **OpenAPI / JSON Tool Definitions** (dapat di-load oleh runtime LLM).
2. **Standard REST Function Endpoints** dengan HMAC / Bearer Token otentikasi.
3. **Execution Guardrail**: Konfirmasi threshold (misal: perubahan budget di atas 30% membutuhkan otorisasi approval).

### 5.1. Daftar Tool Definition (JSON Schema untuk Agent)

Agen Anda dapat mengonsumsi endpoint `GET /api/agent/tools/definitions` yang mereturn:

```json
[
  {
    "name": "get_adset_health_analysis",
    "description": "Menganalisis kesehatan dan efektivitas adset atau campaign berdasarkan metrik ROAS, CPA, CTR, dan frekuensi.",
    "parameters": {
      "type": "object",
      "properties": {
        "adset_id": { "type": "string", "description": "ID AdSet Meta (e.g. 120210...)" },
        "timeframe_days": { "type": "integer", "description": "Rentang hari evaluasi (default: 3)" }
      },
      "required": ["adset_id"]
    }
  },
  {
    "name": "update_adset_status",
    "description": "Mengubah status adset (PAUSED atau ACTIVE) untuk mematikan adset boncos atau mengaktifkan kembali.",
    "parameters": {
      "type": "object",
      "properties": {
        "adset_id": { "type": "string" },
        "action": { "type": "string", "enum": ["PAUSE", "ACTIVATE"] },
        "reason": { "type": "string", "description": "Alasan keputusan untuk audit log" }
      },
      "required": ["adset_id", "action", "reason"]
    }
  },
  {
    "name": "adjust_campaign_budget",
    "description": "Menaikkan atau menurunkan budget harian campaign/adset dalam persentase.",
    "parameters": {
      "type": "object",
      "properties": {
        "entity_type": { "type": "string", "enum": ["CAMPAIGN", "ADSET"] },
        "entity_id": { "type": "string" },
        "percentage_change": { "type": "number", "description": "Persentase penyesuaian (-50 hingga +50)" }
      },
      "required": ["entity_type", "entity_id", "percentage_change"]
    }
  }
]
```

### 5.2. Controller Agent Tooling (`AgentGatewayController.php`)

```php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Analytics\CampaignHealthEvaluator;
use App\Services\Meta\CampaignActionService;
use App\Models\AgentAuditLog;

class AgentGatewayController extends Controller
{
    public function executeTool(
        Request $request, 
        CampaignHealthEvaluator $evaluator,
        CampaignActionService $actionService
    ) {
        $validated = $request->validate([
            'tool_name' => 'required|string',
            'arguments' => 'required|array',
            'agent_id' => 'required|string', // 'hermes' atau 'openclaw'
        ]);

        $tool = $validated['tool_name'];
        $args = $validated['arguments'];
        $response = [];

        switch ($tool) {
            case 'get_adset_health_analysis':
                $adset = \App\Models\AdSet::where('meta_adset_id', $args['adset_id'])->firstOrFail();
                $response = $evaluator->evaluate($adset, $args['timeframe_days'] ?? 3);
                break;

            case 'update_adset_status':
                $status = ($args['action'] === 'PAUSE') ? 'PAUSED' : 'ACTIVE';
                $result = $actionService->setAdsetStatus($args['adset_id'], $status);
                
                // Catat log AI audit
                AgentAuditLog::create([
                    'agent' => $validated['agent_id'],
                    'action' => "SET_STATUS_{$status}",
                    'target_id' => $args['adset_id'],
                    'reason' => $args['reason'] ?? 'AI Decision',
                    'payload' => $args,
                ]);

                $response = ['success' => true, 'new_status' => $status];
                break;

            default:
                return response()->json(['error' => 'Unknown tool'], 400);
        }

        return response()->json([
            'tool' => $tool,
            'result' => $response,
        ]);
    }
}
```

---

## 6. Campaign Management & Execution Engine

Service untuk kontrol campaign langsung ke Graph API (`CampaignActionService.php`):

```php
namespace App\Services\Meta;

use FacebookAds\Object\Campaign;
use FacebookAds\Object\AdSet;
use FacebookAds\Object\Fields\CampaignFields;
use FacebookAds\Object\Fields\AdSetFields;

class CampaignActionService
{
    public function setAdsetStatus(string $metaAdsetId, string $status): bool
    {
        $adSet = new AdSet($metaAdsetId);
        $adSet->updateXml([
            AdSetFields::STATUS => $status, // 'ACTIVE' atau 'PAUSED'
        ]);
        return true;
    }

    public function adjustBudget(string $metaAdsetId, float $percentage): float
    {
        $adSet = new AdSet($metaAdsetId);
        $adSet->read([AdSetFields::DAILY_BUDGET]);
        $currentBudget = (float) $adSet->{AdSetFields::DAILY_BUDGET};

        $newBudget = round($currentBudget * (1 + ($percentage / 100)));
        
        $adSet->updateXml([
            AdSetFields::DAILY_BUDGET => $newBudget,
        ]);

        return $newBudget;
    }

    public function createCampaign(array $data): Campaign
    {
        $account = new \FacebookAds\Object\AdAccount('act_' . $data['meta_account_id']);
        $campaign = $account->createCampaign([], [
            CampaignFields::NAME => $data['name'],
            CampaignFields::OBJECTIVE => $data['objective'],
            CampaignFields::STATUS => Campaign::STATUS_PAUSED, // Safe default: paused dulu
            CampaignFields::SPECIAL_AD_CATEGORIES => ['NONE'],
        ]);

        return $campaign;
    }
}
```

---

## 7. Fitur-Fitur Lanjutan untuk Melipatgandakan ROI Ads

Untuk membuat sistem ini menjadi platform adtech kelas industri, berikut fitur-fitur penting yang disematkan:

### 7.1. Automated Kill-Switch (Pelindung Kebocoran Budget 24/7)
Cron job setiap 15 menit memeriksa:
- **Zero-Purchase Burner**: Jika adset mengeluarkan spend $> 1.5 \times$ Target CPA tanpa penjualan $\rightarrow$ **Auto-pause**.
- **Click-Bait Dropoff**: Jika Link CTR $> 3\%$, tapi Landing Page View $< 40\%$ (indikasi loading lambat/penipuan klik) $\rightarrow$ **Kirim Alert Warning**.

### 7.2. Anti-Fatigue Creative Rotation (Sistem Deteksi Jenuh)
- Meta Ads sering mengalami *ad decay* saat frekuensi naik $> 2.5$.
- Sistem otomatis menghitung `Decay Index`:
  $$\text{Decay Index} = \frac{\text{Outbound CTR (3 Hari Terakhir)}}{\text{Outbound CTR (14 Hari Rata-rata)}}$$
- Jika `Decay Index` $< 0.65$, sistem mengirim sinyal ke AI Agent untuk men-generate variasi copy iklan baru atau menyarankan aktivasi creative cadangan.

### 7.3. Dynamic Budget Scaling (Surfing Budget Rule)
- Menaikkan budget secara cerdas (Horizontal scaling vs Vertical scaling):
  - **Vertical**: Menaikkan budget 15% setiap hari jika ROAS stabil $> 3.0$ (menghindari merusak fase pembelajaran Meta).
  - **Horizontal**: Menduplikasi adset yang winning ke audiens lookalike/broad baru dengan budget segar.

### 7.4. Meta Conversions API (CAPI) Gateway & Event Deduplication
- Integrasi server-side event tracking langsung dari Laravel (misal event `Purchase`, `Lead`, `AddToCart`) menggunakan Facebook Business SDK.
- Mengirimkan `event_id`, IP pengguna, `fbp`, dan `fbc` cookie agar skor Event Quality Meta mencapai $> 8.5/10$, meningkatkan akurasi optimasi algoritma Meta.

### 7.5. AI Ad Copy & Angle Generator
- Mengintegrasikan prompt template ke Hermes / OpenClaw untuk membaca ad copy terbaik (berdasarkan CTR & ROAS tertinggi di database) lalu membuat 3 variasi sudut pandang (Angles):
  - *Angle 1: Pain point / Fear of Missing Out (FOMO)*
  - *Angle 2: Social Proof / Review-driven*
  - *Angle 3: Direct Offer / Scarcity*

### 7.6. Multi-Channel Notification Dispatcher
- Notifikasi instan via **Telegram Bot** atau **Slack Webhook** setiap kali ada aksi penting:
  - 🚨 *"AdSet 'Audience Ibu Hamil' otomatis di-PAUSE karena spend Rp 250.000 tanpa konversi."*
  - 🚀 *"Campaign 'Promo Gajian' terdeteksi ROAS 5.2x! Budget dinaikkan 20% oleh Agent OpenClaw."*

---

## 8. Panduan Langkah Implementasi (Step-by-Step Laravel 12)

### Step 1: Install Laravel 12 & Dependencies
```bash
laravel new webads --pest
cd webads
composer require facebook/php-business-sdk predis/predis
```

### Step 2: Konfigurasi Environment (`.env`)
```env
META_APP_ID=your_meta_app_id
META_APP_SECRET=your_meta_app_secret
META_DEFAULT_ACCESS_TOKEN=your_system_user_token

AGENT_API_KEY=your_secure_random_key_for_hermes_or_openclaw
TELEGRAM_BOT_TOKEN=your_bot_token
TELEGRAM_CHAT_ID=your_chat_id
```

### Step 3: Migration & Model
Jalankan migrasi database untuk tabel `ad_accounts`, `campaigns`, `ad_sets`, `ads`, `ad_insights_daily`, dan `agent_audit_logs`.

### Step 4: Daftarkan Scheduled Tasks di `routes/console.php`
```php
use Illuminate\Support\Facades\Schedule;
use App\Jobs\SyncMetaInsightsJob;
use App\Jobs\RunAutomationRulesJob;

// Sync data dari Meta setiap 30 menit
Schedule::job(new SyncMetaInsightsJob)->everyThirtyMinutes();

// Evaluasi kill switch & rule otomasi setiap 15 menit
Schedule::job(new RunAutomationRulesJob)->everyFifteenMinutes();
```

### Step 5: Daftarkan Route API untuk Agent di `routes/api.php`
```php
use App\Http\Controllers\Api\AgentGatewayController;

Route::prefix('v1/agent')
    ->middleware('auth.agent') // Custom middleware validasi header X-Agent-Key
    ->group(function () {
        Route::get('/tools/definitions', [AgentGatewayController::class, 'getToolDefinitions']);
        Route::post('/tools/execute', [AgentGatewayController::class, 'executeTool']);
    });
```

---
*Dokumen ini dirancang sebagai acuan kerja siap pakai untuk pengembangan sistem Meta Ads terotomasi dan terintegrasi AI di Laravel 12.*
