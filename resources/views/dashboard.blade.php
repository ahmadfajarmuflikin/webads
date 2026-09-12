<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meta Ads AI Manager & Smart Optimizer - Laravel 12</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        brand: { 500: '#1877F2', 600: '#0d65d9' },
                        surface: { 800: '#1e293b', 900: '#0f172a' }
                    }
                }
            }
        }
    </script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-950 text-slate-100 font-sans antialiased min-h-screen">

    <!-- Top Navigation -->
    <header class="border-b border-slate-800 bg-slate-900/80 backdrop-blur sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-500 flex items-center justify-center text-white shadow-lg shadow-blue-500/30">
                    <i class="fa-brands fa-meta text-xl"></i>
                </div>
                <div>
                    <h1 class="font-bold text-lg leading-tight flex items-center gap-2">
                        Smart Meta Ads Agentic Co-Pilot
                        <span class="text-xs bg-blue-500/20 text-blue-400 border border-blue-500/30 px-2 py-0.5 rounded-full font-mono">Laravel 12</span>
                    </h1>
                    <p class="text-xs text-slate-400">Hermes & OpenClaw Tool-Use Integration</p>
                </div>
            </div>

            <div class="flex items-center space-x-4">
                <div class="hidden sm:flex items-center space-x-3 text-xs bg-slate-800/80 border border-slate-700/60 px-3 py-1.5 rounded-lg">
                    <span class="text-slate-400">Akun:</span>
                    <span class="font-semibold text-white">{{ $account?->name ?? 'Meta Account' }}</span>
                    <span class="text-slate-500">|</span>
                    <span class="text-slate-400">Target ROAS:</span>
                    <span class="font-bold text-emerald-400">{{ $account?->target_roas }}x</span>
                    <span class="text-slate-500">|</span>
                    <span class="text-slate-400">Target CPA:</span>
                    <span class="font-bold text-blue-400">Rp {{ number_format($account?->target_cpa ?? 0, 0, ',', '.') }}</span>
                </div>

                <a href="{{ route('auth.meta.redirect') }}" class="inline-flex items-center space-x-2 bg-[#1877F2] hover:bg-blue-600 text-white text-xs font-semibold px-4 py-2 rounded-lg shadow-md transition shadow-blue-900/40" title="Login & Hubungkan Akun Iklan via Meta OAuth">
                    <i class="fa-brands fa-facebook text-sm"></i>
                    <span>Connect with Meta</span>
                </a>

                <form action="{{ route('automation.watchdog') }}" method="POST">
                    @csrf
                    <button type="submit" class="inline-flex items-center space-x-2 bg-gradient-to-r from-red-600 to-orange-600 hover:from-red-500 hover:to-orange-500 text-white text-xs font-semibold px-4 py-2 rounded-lg shadow-md transition shadow-orange-950/40">
                        <i class="fa-solid fa-bolt"></i>
                        <span>Run Watchdog</span>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">

        <!-- Flash Messages -->
        @if(session('success'))
        <div class="bg-emerald-950/60 border border-emerald-500/40 text-emerald-300 px-4 py-3 rounded-xl flex items-center space-x-3 text-sm">
            <i class="fa-solid fa-circle-check text-emerald-400"></i>
            <span>{{ session('success') }}</span>
        </div>
        @endif

        @if(session('error'))
        <div class="bg-red-950/60 border border-red-500/40 text-red-300 px-4 py-3 rounded-xl flex items-center space-x-3 text-sm">
            <i class="fa-solid fa-circle-exclamation text-red-400"></i>
            <span>{{ session('error') }}</span>
        </div>
        @endif

        <!-- KPI Metric Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-sm">
                <div class="flex items-center justify-between text-slate-400 text-xs font-medium">
                    <span>Total Spend</span>
                    <i class="fa-solid fa-wallet text-slate-500"></i>
                </div>
                <div class="mt-2 text-2xl font-bold text-white tracking-tight">
                    Rp {{ number_format($kpis['total_spend'], 0, ',', '.') }}
                </div>
                <div class="mt-1 text-xs text-slate-500">Akumulasi pengeluaran iklan</div>
            </div>

            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-sm">
                <div class="flex items-center justify-between text-slate-400 text-xs font-medium">
                    <span>Total Konversi</span>
                    <i class="fa-solid fa-cart-shopping text-slate-500"></i>
                </div>
                <div class="mt-2 text-2xl font-bold text-emerald-400 tracking-tight">
                    {{ number_format($kpis['total_conversions']) }}
                </div>
                <div class="mt-1 text-xs text-slate-500">Purchases & Leads</div>
            </div>

            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-sm">
                <div class="flex items-center justify-between text-slate-400 text-xs font-medium">
                    <span>Total Revenue</span>
                    <i class="fa-solid fa-money-bill-trend-up text-slate-500"></i>
                </div>
                <div class="mt-2 text-2xl font-bold text-indigo-400 tracking-tight">
                    Rp {{ number_format($kpis['total_revenue'], 0, ',', '.') }}
                </div>
                <div class="mt-1 text-xs text-slate-500">Omzet dari konversi ads</div>
            </div>

            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-sm">
                <div class="flex items-center justify-between text-slate-400 text-xs font-medium">
                    <span>Overall ROAS</span>
                    <i class="fa-solid fa-chart-line text-slate-500"></i>
                </div>
                <div class="mt-2 text-2xl font-bold {{ $kpis['overall_roas'] >= ($account?->target_roas ?? 2.5) ? 'text-emerald-400' : 'text-amber-400' }} tracking-tight">
                    {{ number_format($kpis['overall_roas'], 2) }}x
                </div>
                <div class="mt-1 text-xs text-slate-500">Target: {{ $account?->target_roas }}x</div>
            </div>

            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-sm">
                <div class="flex items-center justify-between text-slate-400 text-xs font-medium">
                    <span>Avg. CPA</span>
                    <i class="fa-solid fa-bullseye text-slate-500"></i>
                </div>
                <div class="mt-2 text-2xl font-bold text-blue-400 tracking-tight">
                    Rp {{ number_format($kpis['overall_cpa'], 0, ',', '.') }}
                </div>
                <div class="mt-1 text-xs text-slate-500">Cost per acquisition</div>
            </div>
        </div>

        <!-- Smart System Evaluation Table -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
            <div class="px-6 py-5 border-b border-slate-800 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-base font-bold text-white flex items-center gap-2">
                        <i class="fa-solid fa-brain text-indigo-400"></i>
                        Smart Evaluation Engine & Control Panel
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">Analisis efektivitas performa 3 hari terakhir dengan rekomendasi aksi otomatis</p>
                </div>
                <div class="flex items-center gap-2 text-xs">
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-emerald-950/60 border border-emerald-600/40 text-emerald-400">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span> SCALING
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-red-950/60 border border-red-600/40 text-red-400">
                        <span class="w-1.5 h-1.5 rounded-full bg-red-400"></span> BONCOS / KILL
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-amber-950/60 border border-amber-600/40 text-amber-400">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span> FATIGUE
                    </span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-950/60 text-slate-400 uppercase tracking-wider font-medium border-b border-slate-800">
                        <tr>
                            <th class="px-6 py-4">AdSet & Status</th>
                            <th class="px-4 py-4">Health Score</th>
                            <th class="px-4 py-4">Status Efektivitas</th>
                            <th class="px-4 py-4">Spend & Konversi</th>
                            <th class="px-4 py-4">ROAS & CPA</th>
                            <th class="px-4 py-4">Frekuensi & CTR</th>
                            <th class="px-4 py-4">Diagnosa Smart System</th>
                            <th class="px-6 py-4 text-right">Aksi Kontrol</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/80">
                        @forelse($evaluatedAdsets as $item)
                        @php
                            $m = $item['metrics'];
                            $verdictColors = [
                                'SCALING_CANDIDATE' => 'bg-emerald-950/80 border-emerald-500 text-emerald-300',
                                'LOSING_MONEY' => 'bg-red-950/80 border-red-500 text-red-300',
                                'CREATIVE_FATIGUE' => 'bg-amber-950/80 border-amber-500 text-amber-300',
                                'HEALTHY_STABLE' => 'bg-blue-950/80 border-blue-500 text-blue-300',
                                'LEARNING_PHASE' => 'bg-slate-800 border-slate-600 text-slate-300',
                            ];
                            $vClass = $verdictColors[$item['verdict']] ?? 'bg-slate-800 border-slate-600 text-slate-300';
                        @endphp
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-6 py-4">
                                <div class="font-semibold text-white text-sm">{{ $item['name'] }}</div>
                                <div class="text-[11px] text-slate-400 mt-0.5 flex items-center gap-2 font-mono">
                                    <span>ID: {{ $item['meta_id'] }}</span>
                                    <span>•</span>
                                    <span>Budget: Rp {{ number_format($item['current_daily_budget'], 0, ',', '.') }}/hari</span>
                                    <span>•</span>
                                    <span class="inline-block px-1.5 py-0.2 rounded {{ $item['status'] === 'ACTIVE' ? 'bg-emerald-500/20 text-emerald-400' : 'bg-slate-700 text-slate-400' }} text-[10px]">
                                        {{ $item['status'] }}
                                    </span>
                                </div>
                            </td>

                            <td class="px-4 py-4">
                                <div class="flex items-center space-x-2">
                                    <span class="font-bold text-sm {{ $item['health_score'] >= 80 ? 'text-emerald-400' : ($item['health_score'] < 40 ? 'text-red-400' : 'text-amber-400') }}">
                                        {{ $item['health_score'] }}/100
                                    </span>
                                </div>
                                <div class="w-20 bg-slate-800 rounded-full h-1.5 mt-1 overflow-hidden">
                                    <div class="h-1.5 rounded-full {{ $item['health_score'] >= 80 ? 'bg-emerald-500' : ($item['health_score'] < 40 ? 'bg-red-500' : 'bg-amber-500') }}" style="width: {{ $item['health_score'] }}%"></div>
                                </div>
                            </td>

                            <td class="px-4 py-4">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[11px] font-bold border {{ $vClass }}">
                                    {{ $item['verdict'] }}
                                </span>
                            </td>

                            <td class="px-4 py-4">
                                <div class="font-semibold text-white">Rp {{ number_format($m['spend'], 0, ',', '.') }}</div>
                                <div class="text-emerald-400 font-bold mt-0.5">{{ $m['conversions'] }} Konversi</div>
                            </td>

                            <td class="px-4 py-4">
                                <div class="font-bold text-sm {{ $m['roas'] >= $m['target_roas'] ? 'text-emerald-400' : 'text-amber-400' }}">
                                    {{ $m['roas'] }}x ROAS
                                </div>
                                <div class="text-[11px] text-slate-400 mt-0.5">
                                    CPA: Rp {{ number_format($m['cpa'], 0, ',', '.') }}
                                </div>
                            </td>

                            <td class="px-4 py-4">
                                <div class="text-slate-200">
                                    Freq: <span class="font-bold {{ $m['frequency'] > 2.8 ? 'text-amber-400' : 'text-white' }}">{{ $m['frequency'] }}</span>
                                </div>
                                <div class="text-[11px] text-slate-400 mt-0.5">
                                    CTR: <span class="font-semibold {{ $m['ctr'] < 1.0 ? 'text-red-400' : 'text-slate-200' }}">{{ $m['ctr'] }}%</span>
                                </div>
                            </td>

                            <td class="px-4 py-4 max-w-xs">
                                <ul class="text-[11px] text-slate-300 space-y-1">
                                    @foreach($item['reasons'] as $r)
                                        <li class="flex items-start gap-1">
                                            <span class="text-indigo-400">•</span>
                                            <span>{{ $r }}</span>
                                        </li>
                                    @endforeach
                                </ul>
                            </td>

                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end space-x-2">
                                    @if($item['status'] === 'ACTIVE')
                                        <!-- Scale Up +20% -->
                                        <form action="{{ route('adset.scale', $item['meta_id']) }}" method="POST" class="inline">
                                            @csrf
                                            <input type="hidden" name="percentage" value="20">
                                            <button type="submit" class="px-2.5 py-1.5 bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-400 border border-emerald-500/30 rounded-lg text-xs font-semibold transition" title="Scale Budget +20%">
                                                +20% Scale
                                            </button>
                                        </form>

                                        <!-- Pause Adset -->
                                        <form action="{{ route('adset.status', $item['meta_id']) }}" method="POST" class="inline">
                                            @csrf
                                            <input type="hidden" name="status" value="PAUSED">
                                            <button type="submit" class="px-2.5 py-1.5 bg-red-600/20 hover:bg-red-600/30 text-red-400 border border-red-500/30 rounded-lg text-xs font-semibold transition" title="Pause Adset">
                                                <i class="fa-solid fa-pause"></i> Pause
                                            </button>
                                        </form>
                                    @else
                                        <!-- Activate Adset -->
                                        <form action="{{ route('adset.status', $item['meta_id']) }}" method="POST" class="inline">
                                            @csrf
                                            <input type="hidden" name="status" value="ACTIVE">
                                            <button type="submit" class="px-2.5 py-1.5 bg-blue-600/20 hover:bg-blue-600/30 text-blue-400 border border-blue-500/30 rounded-lg text-xs font-semibold transition" title="Aktifkan Kembali">
                                                <i class="fa-solid fa-play"></i> Activate
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-8 text-slate-500">Belum ada adset yang disinkronkan.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Grid 2 Kolom: AI Agent Integration & Live Audit Logs -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Card 1: OpenClaw & Hermes AI Integration Interface -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="font-bold text-white text-sm flex items-center gap-2">
                        <i class="fa-solid fa-robot text-purple-400"></i>
                        Agent Interface (Hermes / OpenClaw)
                    </h3>
                    <span class="text-xs bg-purple-500/20 text-purple-300 border border-purple-500/30 px-2 py-0.5 rounded-full font-mono">
                        Tool-Calling V1
                    </span>
                </div>

                <p class="text-xs text-slate-400">
                    Sistem ini menyediakan endpoint tool standard yang dapat langsung dihubungkan dengan runtime LLM Anda.
                </p>

                <div class="space-y-2 text-xs font-mono">
                    <div class="bg-slate-950 p-3 rounded-lg border border-slate-800 text-slate-300">
                        <div class="text-slate-500 font-sans mb-1">Tool Definitions (JSON Schema):</div>
                        <span class="text-emerald-400">GET</span> {{ url('/api/v1/agent/tools/definitions') }}
                    </div>
                    <div class="bg-slate-950 p-3 rounded-lg border border-slate-800 text-slate-300">
                        <div class="text-slate-500 font-sans mb-1">Execute Tool Endpoint:</div>
                        <span class="text-blue-400">POST</span> {{ url('/api/v1/agent/tools/execute') }}
                    </div>
                    <div class="bg-slate-950 p-3 rounded-lg border border-slate-800 text-slate-300">
                        <div class="text-slate-500 font-sans mb-1">Authentication Header:</div>
                        <span class="text-amber-400">X-Agent-Key:</span> {{ config('meta.agent_api_key') }}
                    </div>
                </div>

                <div class="bg-slate-950/60 p-3 rounded-lg border border-slate-800/80 text-[11px] text-slate-400">
                    <span class="text-indigo-400 font-semibold">Tersedia Tools:</span>
                    <ul class="list-disc list-inside mt-1 space-y-0.5">
                        <li><code>get_adset_health_analysis</code> (Evaluasi performa cerdas)</li>
                        <li><code>update_adset_status</code> (Pause / Aktifkan adset)</li>
                        <li><code>adjust_adset_budget</code> (Scale / Descale budget)</li>
                        <li><code>create_new_campaign</code> (Prompt to Meta campaign)</li>
                        <li><code>generate_creative_angles</code> (AI Copywriter saat fatigue)</li>
                    </ul>
                </div>
            </div>

            <!-- Card 2: Live AI & Automation Audit Trail -->
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="font-bold text-white text-sm flex items-center gap-2">
                        <i class="fa-solid fa-clock-rotate-left text-blue-400"></i>
                        Agent & Watchdog Audit Trail
                    </h3>
                    <span class="text-xs text-slate-400">{{ $auditLogs->count() }} Aktivitas Terakhir</span>
                </div>

                <div class="space-y-3 max-h-72 overflow-y-auto pr-1 text-xs">
                    @forelse($auditLogs as $log)
                    <div class="bg-slate-950 p-3 rounded-xl border border-slate-800/80 flex items-start space-x-3">
                        <div class="w-7 h-7 rounded-lg bg-indigo-950 border border-indigo-500/30 flex items-center justify-center text-indigo-400 flex-shrink-0 mt-0.5">
                            <i class="fa-solid fa-microchip text-xs"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-200">{{ $log->action }}</span>
                                <span class="text-[10px] text-slate-500 font-mono">{{ $log->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="text-slate-400 mt-1 text-[11px]">{{ $log->reason }}</p>
                            <div class="mt-1.5 flex items-center gap-2 text-[10px] font-mono text-slate-500">
                                <span>Agent: <strong class="text-indigo-400">{{ $log->agent_id }}</strong></span>
                                <span>• Target: <code class="text-slate-400">{{ $log->target_id }}</code></span>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="text-center py-6 text-slate-500">Belum ada riwayat audit agent.</div>
                    @endforelse
                </div>
            </div>

        </div>

    </main>

    <footer class="border-t border-slate-800 py-6 text-center text-xs text-slate-500">
        Meta Ads AI Co-Pilot &copy; {{ date('Y') }} Built on Laravel 12 & Facebook Marketing API.
    </footer>

</body>
</html>
