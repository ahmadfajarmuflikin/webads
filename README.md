# 🚀 Smart Meta Ads Manager & Agentic Co-Pilot (Laravel 12)

Aplikasi web modern berbasis **Laravel 12** yang terintegrasi dengan **Meta Marketing API v20.0+**, dilengkapi **Smart Evaluation Engine** (penilai efektivitas ads otomatis), **24/7 Automated Kill-Switch / Scaling Watchdog**, serta protokol tool-calling untuk AI Agents seperti **Hermes** dan **OpenClaw**.

---

## ✨ Fitur Utama

1. **Meta Marketing API v20.0+ Engine**
   - Menggunakan official SDK `facebook/php-business-sdk`.
   - Mengelola Campaign, AdSet, Ad, dan Ad Creative.
   - Sinkronisasi metrik Insights harian (Spend, Impressions, Clicks, CPC, CTR, Frequency, Conversions, ROAS, CPA).
2. **Smart Evaluation Engine (Penilai Efektivitas Iklan)**
   - 🟢 `SCALING_CANDIDATE`: ROAS $\ge 1.25\times$ target, CPA rendah, frekuensi $< 2.5$.
   - 🔴 `LOSING_MONEY`: Spend $\ge 1.5\times$ target CPA dengan 0 konversi (Auto Kill-Switch).
   - 🟠 `CREATIVE_FATIGUE`: Frekuensi $\ge 2.8$ dan CTR anjlok $> 30\%$.
   - 🟡 `HEALTHY_STABLE` & ⚪ `LEARNING_PHASE`.
3. **Hermes & OpenClaw AI Agent Gateway**
   - Endpoint discovery standar: `GET /api/v1/agent/tools/definitions`.
   - Endpoint eksekusi tool: `POST /api/v1/agent/tools/execute`.
   - Autentikasi aman via header `X-Agent-Key` atau `Bearer` token.
   - Audit trail setiap aksi yang diputuskan oleh LLM (`agent_audit_logs`).
4. **Campaign Control & Execution**
   - Pause / Resume campaign & adset secara real-time.
   - Dynamic budget adjustment (naik/turun bertahap dalam persentase).
   - Pembuatan campaign baru berbasis AI prompts.
5. **Fitur Nilai Tambah (Ad Performance Boosters)**
   - **Conversions API (CAPI) Gateway**: Pelacakan server-side dengan `event_id` deduplikasi.
   - **Decay Index**: Deteksi dini kelelahan materi iklan sebelum biaya membengkak.
   - **AI Copy & Angle Generator**: 3 variasi sudut pandang copywriting iklan baru saat terjadi kejenuhan audiens.

---

## 🛠️ Cara Menjalankan Aplikasi

### 1. Jalankan Server Lokal
```bash
php artisan serve
```
Buka browser di: [http://127.0.0.1:8000](http://127.0.0.1:8000)

### 2. Jalankan Queue Worker & Background Scheduler
```bash
# Untuk menjalankan worker sinkronisasi & kill switch otomatis
php artisan queue:work

# Untuk menjalankan scheduler berkala
php artisan schedule:work
```

### 3. Jalankan Automated Test Suite
```bash
php artisan test
```

---

## 🤖 Menghubungkan ke Agen AI (Hermes / OpenClaw)

Setiap agen AI dapat berinteraksi dengan API menggunakan format standar Function Calling / Tool Use:

### 1. Dapatkan Definisi Tools
```bash
curl -X GET http://127.0.0.1:8000/api/v1/agent/tools/definitions \
  -H "X-Agent-Key: hermes_secret_meta_agent_key_2026"
```

### 2. Contoh Eksekusi Analisis AdSet
```bash
curl -X POST http://127.0.0.1:8000/api/v1/agent/tools/execute \
  -H "Content-Type: application/json" \
  -H "X-Agent-Key: hermes_secret_meta_agent_key_2026" \
  -d '{
    "agent_id": "hermes-70b",
    "tool_name": "get_adset_health_analysis",
    "arguments": {
      "adset_id": "adset_boncos_002",
      "timeframe_days": 3
    }
  }'
```

### 3. Contoh Eksekusi Pause AdSet Boncos
```bash
curl -X POST http://127.0.0.1:8000/api/v1/agent/tools/execute \
  -H "Content-Type: application/json" \
  -H "X-Agent-Key: hermes_secret_meta_agent_key_2026" \
  -d '{
    "agent_id": "openclaw-agent",
    "tool_name": "update_adset_status",
    "arguments": {
      "adset_id": "adset_boncos_002",
      "action": "PAUSE",
      "reason": "Spend sudah mencapai 2x target CPA tanpa konversi."
    }
  }'
```

### 4. Contoh Eksekusi Scale Budget AdSet Winning (+20%)
```bash
curl -X POST http://127.0.0.1:8000/api/v1/agent/tools/execute \
  -H "Content-Type: application/json" \
  -H "X-Agent-Key: hermes_secret_meta_agent_key_2026" \
  -d '{
    "agent_id": "hermes-agent",
    "tool_name": "adjust_adset_budget",
    "arguments": {
      "adset_id": "adset_winner_001",
      "percentage_change": 20,
      "reason": "ROAS 3.8x stabil dan frekuensi audiens masih segar."
    }
  }'
```
