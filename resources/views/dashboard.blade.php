<!DOCTYPE html>
<html lang="id" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
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

    <!-- Top Navigation (Simplified & Modern) -->
    <header class="border-b border-slate-800 bg-slate-900/90 backdrop-blur sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <!-- Left: Brand & Active Account Pill -->
            <div class="flex items-center space-x-3">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-blue-600 to-indigo-500 flex items-center justify-center text-white shadow-md shadow-blue-500/20">
                    <i class="fa-brands fa-meta text-lg"></i>
                </div>
                <div class="flex items-center space-x-2">
                    <span class="font-bold text-base text-white tracking-tight">Adstool</span>
                    <span class="text-slate-600">/</span>
                    <div class="flex items-center gap-1.5 bg-slate-800/80 border border-slate-700/60 px-2.5 py-1 rounded-lg text-xs">
                        <span class="w-2 h-2 rounded-full {{ $account ? 'bg-emerald-400 animate-pulse' : 'bg-slate-500' }}"></span>
                        <span class="font-semibold text-slate-200">{{ $account?->name ?? 'Belum Terhubung' }}</span>
                        @if($account)
                            <span class="text-slate-500 text-[11px]">({{ $account->currency }})</span>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Right: Simplified Action Center -->
            <div class="flex items-center space-x-2.5">
                <!-- 1. Sync Data -->
                <form action="{{ route('meta.sync_web') }}" method="POST" class="inline-flex items-center bg-slate-800/80 border border-slate-700/80 p-0.5 rounded-lg text-xs">
                    @csrf
                    <select name="preset" class="bg-transparent text-slate-300 text-xs px-2 py-1.5 focus:outline-none cursor-pointer">
                        <option value="today" class="bg-slate-900">Hari Ini</option>
                        <option value="yesterday" class="bg-slate-900">Kemarin</option>
                        <option value="last_3d" class="bg-slate-900">3 Hari</option>
                        <option value="last_7d" selected class="bg-slate-900">7 Hari</option>
                        <option value="last_30d" class="bg-slate-900">30 Hari</option>
                    </select>
                    <button type="submit" class="inline-flex items-center space-x-1 bg-indigo-600 hover:bg-indigo-500 text-white font-semibold px-2.5 py-1.5 rounded-md transition" title="Tarik data terbaru dari Meta">
                        <i class="fa-solid fa-arrows-rotate text-xs"></i>
                        <span>Sync</span>
                    </button>
                </form>

                <!-- 2. Dropdown: + Buat Iklan (Menggabungkan Campaign & Creative) -->
                <div class="relative inline-block text-left" id="dropdownCreateContainer">
                    <button type="button" onclick="toggleDropdown('dropdownCreateMenu')" class="inline-flex items-center space-x-1.5 bg-blue-600 hover:bg-blue-500 text-white text-xs font-semibold px-3 py-2 rounded-lg shadow-md transition shadow-blue-900/30">
                        <i class="fa-solid fa-plus text-xs"></i>
                        <span>Buat Iklan</span>
                        <i class="fa-solid fa-chevron-down text-[10px]"></i>
                    </button>
                    <div id="dropdownCreateMenu" class="hidden absolute right-0 mt-2 w-56 rounded-xl bg-slate-900 border border-slate-800 shadow-2xl p-1.5 z-50 space-y-1">
                        <button type="button" onclick="document.getElementById('createCampaignModal').classList.remove('hidden'); toggleDropdown('dropdownCreateMenu')" class="w-full flex items-center space-x-2.5 px-3 py-2 text-xs text-left text-slate-200 hover:bg-slate-800 rounded-lg transition">
                            <i class="fa-solid fa-bullhorn text-emerald-400 w-4 text-center"></i>
                            <div>
                                <div class="font-bold">Buat Campaign Baru</div>
                                <div class="text-[10px] text-slate-400">Struktur campaign Meta baru</div>
                            </div>
                        </button>
                        <button type="button" onclick="document.getElementById('creativeStudioModal').classList.remove('hidden'); toggleDropdown('dropdownCreateMenu')" class="w-full flex items-center space-x-2.5 px-3 py-2 text-xs text-left text-slate-200 hover:bg-slate-800 rounded-lg transition">
                            <i class="fa-solid fa-wand-magic-sparkles text-purple-400 w-4 text-center"></i>
                            <div>
                                <div class="font-bold">Upload Creative</div>
                                <div class="text-[10px] text-slate-400">Gambar, Video, atau Carousel</div>
                            </div>
                        </button>
                    </div>
                </div>

                <!-- 3. Tombol Watchdog -->
                <form action="{{ route('automation.watchdog') }}" method="POST">
                    @csrf
                    <button type="submit" class="inline-flex items-center space-x-1.5 bg-slate-800 hover:bg-slate-700 text-amber-400 border border-slate-700 text-xs font-semibold px-3 py-2 rounded-lg transition" title="Jalankan Otomasi Kill-Switch & Scale">
                        <i class="fa-solid fa-bolt"></i>
                        <span class="text-slate-200">Watchdog</span>
                    </button>
                </form>

                <!-- 4. Dropdown: Pengaturan Akun & Koneksi Token -->
                <div class="relative inline-block text-left" id="dropdownAccountContainer">
                    <button type="button" onclick="toggleDropdown('dropdownAccountMenu')" class="p-2 bg-slate-800/80 hover:bg-slate-700 text-slate-300 hover:text-white rounded-lg border border-slate-700 transition" title="Pengaturan Akun & Koneksi">
                        <i class="fa-solid fa-gear text-sm"></i>
                    </button>
                    <div id="dropdownAccountMenu" class="hidden absolute right-0 mt-2 w-60 rounded-xl bg-slate-900 border border-slate-800 shadow-2xl p-1.5 z-50 space-y-1">
                        <button type="button" onclick="document.getElementById('manualTokenModal').classList.remove('hidden'); toggleDropdown('dropdownAccountMenu')" class="w-full flex items-center space-x-2 px-3 py-2 text-xs text-left text-slate-200 hover:bg-slate-800 rounded-lg transition">
                            <i class="fa-solid fa-key text-amber-400 w-4 text-center"></i>
                            <div>
                                <div class="font-bold">Input Token Manual</div>
                                <div class="text-[10px] text-slate-400">System User / Graph API Token</div>
                            </div>
                        </button>
                        <a href="{{ route('auth.meta.redirect') }}" class="w-full flex items-center space-x-2 px-3 py-2 text-xs text-left text-slate-200 hover:bg-slate-800 rounded-lg transition">
                            <i class="fa-brands fa-facebook text-blue-400 w-4 text-center"></i>
                            <div>
                                <div class="font-bold">Connect via OAuth</div>
                                <div class="text-[10px] text-slate-400">Login browser Facebook</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Modal Buat Campaign Baru -->
    <div id="createCampaignModal" class="hidden fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-xl w-full p-6 shadow-2xl space-y-4 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 class="font-bold text-white text-base flex items-center gap-2">
                    <i class="fa-solid fa-bullhorn text-emerald-400"></i>
                    Buat Campaign Baru ke Meta Ads
                </h3>
                <button type="button" onclick="document.getElementById('createCampaignModal').classList.add('hidden')" class="text-slate-400 hover:text-white text-lg">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <p class="text-xs text-slate-400 leading-relaxed">
                Campaign akan langsung dibuat dan dikirimkan ke akun <strong>{{ $account?->name ?? 'Meta Ads' }}</strong> via Marketing API.
            </p>

            <form action="{{ route('campaign.create') }}" method="POST" class="space-y-4 text-xs">
                @csrf
                <div>
                    <label class="block text-slate-300 font-semibold mb-1">Nama Campaign <span class="text-red-400">*</span></label>
                    <input type="text" name="name" placeholder="contoh: [Promo Diskon 50%] Undangan Digital Elegan" required class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-white placeholder-slate-600 focus:outline-none focus:border-emerald-500 font-medium">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">Objektif Iklan <span class="text-red-400">*</span></label>
                        <select name="objective" required class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-emerald-500">
                            <option value="OUTCOME_SALES">Penjualan (Sales / Purchases)</option>
                            <option value="OUTCOME_LEADS">Prospek / Kontak (Leads)</option>
                            <option value="OUTCOME_TRAFFIC">Kunjungan Web (Traffic)</option>
                            <option value="OUTCOME_ENGAGEMENT">Interaksi (Engagement)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">Status Awal <span class="text-red-400">*</span></label>
                        <select name="status" required class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-emerald-500">
                            <option value="PAUSED" selected>PAUSED (Direkomendasikan)</option>
                            <option value="ACTIVE">ACTIVE (Langsung Tayang)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">Budget Harian Campaign (IDR)</label>
                        <input type="number" name="daily_budget" step="10000" placeholder="contoh: 150000" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-white placeholder-slate-600 focus:outline-none focus:border-emerald-500 font-mono">
                        <span class="text-[10px] text-slate-500">Opsional jika menggunakan CBO</span>
                    </div>

                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">Nama AdSet Pertama</label>
                        <input type="text" name="adset_name" placeholder="contoh: Broad Wanita 20-35" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-white placeholder-slate-600 focus:outline-none focus:border-emerald-500">
                        <span class="text-[10px] text-slate-500">Opsional: membuat grup iklan sekaligus</span>
                    </div>
                </div>

                <!-- Section Materi Iklan (Creative Gambar & Video) -->
                <div class="p-3.5 bg-slate-950/70 border border-slate-800 rounded-xl space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="font-semibold text-slate-200 flex items-center gap-1.5">
                            <i class="fa-solid fa-photo-film text-purple-400"></i>
                            Materi Iklan (Creative Asset)
                        </span>
                        <span class="text-[10px] bg-slate-800 text-slate-400 px-2 py-0.5 rounded">Opsional</span>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-slate-400 mb-1">Format Media</label>
                            <select name="ad_media_type" class="w-full bg-slate-900 border border-slate-800 rounded-lg px-2.5 py-1.5 text-white focus:outline-none focus:border-purple-500">
                                <option value="IMAGE">🖼️ Gambar / Banner (Image)</option>
                                <option value="VIDEO">🎬 Video (Reels / Feed)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-slate-400 mb-1">Call to Action (CTA)</label>
                            <select name="ad_cta" class="w-full bg-slate-900 border border-slate-800 rounded-lg px-2.5 py-1.5 text-white focus:outline-none focus:border-purple-500">
                                <option value="ORDER_NOW">Pesan Sekarang (Order Now)</option>
                                <option value="SEND_WHATSAPP_MESSAGE">Kirim Pesan WhatsApp</option>
                                <option value="LEARN_MORE">Selengkapnya (Learn More)</option>
                                <option value="SHOP_NOW">Beli Sekarang (Shop Now)</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-slate-400 mb-1">Headline Iklan (Judul Singkat)</label>
                        <input type="text" name="ad_headline" placeholder="contoh: Solusi Undangan Pernikahan Mewah Mulai 99rb" class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-1.5 text-white placeholder-slate-600 focus:outline-none focus:border-purple-500">
                    </div>

                    <div>
                        <label class="block text-slate-400 mb-1">Teks Utama Iklan (Primary Text)</label>
                        <textarea name="ad_primary_text" rows="2" placeholder="contoh: Buat undangan digital tanpa ribet dengan fitur RSVP, musik, galeri foto, & buku tamu otomatis..." class="w-full bg-slate-900 border border-slate-800 rounded-lg px-3 py-1.5 text-white placeholder-slate-600 focus:outline-none focus:border-purple-500"></textarea>
                    </div>
                </div>

                <!-- AI Copywriting Inspiration Preview -->
                <div class="bg-slate-950/80 p-3 rounded-xl border border-slate-800/80 space-y-2">
                    <div class="flex items-center justify-between text-[11px]">
                        <span class="font-semibold text-indigo-400 flex items-center gap-1">
                            <i class="fa-solid fa-wand-magic-sparkles"></i> AI Angle Generator
                        </span>
                        <span class="text-slate-500">Inspirasi Sudut Pandang Iklan</span>
                    </div>
                    <div class="text-[11px] text-slate-400 space-y-1">
                        <p>💡 <strong>Sudut FOMO:</strong> "Masih bingung sebar undangan mepet? Buat undangan digital elegan hanya dalam 5 menit!"</p>
                        <p>💡 <strong>Sudut Social Proof:</strong> "Sudah dipercaya 5.000+ calon pengantin di seluruh Indonesia. Cek ratusan temanya!"</p>
                    </div>
                </div>

                <div class="pt-3 flex items-center justify-end space-x-2 border-t border-slate-800">
                    <button type="button" onclick="document.getElementById('createCampaignModal').classList.add('hidden')" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg font-semibold">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg font-semibold shadow-lg shadow-emerald-950/40 flex items-center gap-2">
                        <i class="fa-solid fa-paper-plane"></i>
                        <span>Kirim & Buat di Meta</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Creative Studio (Upload Gambar, Video, Carousel) -->
    <div id="creativeStudioModal" class="hidden fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4 overflow-y-auto">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-2xl w-full p-6 shadow-2xl space-y-4 my-8 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <div>
                    <h3 class="font-bold text-white text-base flex items-center gap-2">
                        <i class="fa-solid fa-wand-magic-sparkles text-purple-400"></i>
                        Creative Studio & Meta Ad Uploader
                    </h3>
                    <p class="text-xs text-slate-400 mt-0.5">Upload Single Image, Video, atau Carousel langsung ke Meta Graph API</p>
                </div>
                <button type="button" onclick="document.getElementById('creativeStudioModal').classList.add('hidden')" class="text-slate-400 hover:text-white text-lg">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form action="{{ route('creative.upload') }}" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs">
                @csrf

                <!-- Target AdSet -->
                <div>
                    <label class="block text-slate-300 font-semibold mb-1">Target AdSet</label>
                    <select name="adset_id" required class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-purple-500 font-medium cursor-pointer">
                        @foreach($evaluatedAdsets as $item)
                            <option value="{{ $item['model']->id }}">
                                [{{ $item['verdict'] }}] {{ $item['name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Nama Iklan -->
                <div>
                    <label class="block text-slate-300 font-semibold mb-1">Nama Iklan (Ad Name)</label>
                    <input type="text" name="ad_name" placeholder="contoh: Promo Serum - Single Image UGC" required class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-white placeholder-slate-600 focus:outline-none focus:border-purple-500">
                </div>

                <!-- Pilihan Format Creative -->
                <div>
                    <label class="block text-slate-300 font-semibold mb-1">Pilih Format Creative</label>
                    <div class="grid grid-cols-3 gap-3">
                        <label class="flex items-center gap-2 p-2.5 bg-slate-950 border border-slate-800 rounded-xl cursor-pointer hover:border-purple-500 transition">
                            <input type="radio" name="format" value="IMAGE" checked onchange="switchFormat('IMAGE')" class="text-purple-600 focus:ring-0">
                            <div>
                                <div class="font-bold text-slate-200">Single Image</div>
                                <div class="text-[10px] text-slate-500">JPG/PNG 1:1 atau 4:5</div>
                            </div>
                        </label>
                        <label class="flex items-center gap-2 p-2.5 bg-slate-950 border border-slate-800 rounded-xl cursor-pointer hover:border-purple-500 transition">
                            <input type="radio" name="format" value="VIDEO" onchange="switchFormat('VIDEO')" class="text-purple-600 focus:ring-0">
                            <div>
                                <div class="font-bold text-slate-200">Single Video</div>
                                <div class="text-[10px] text-slate-500">MP4 Reels/Feed 9:16</div>
                            </div>
                        </label>
                        <label class="flex items-center gap-2 p-2.5 bg-slate-950 border border-slate-800 rounded-xl cursor-pointer hover:border-purple-500 transition">
                            <input type="radio" name="format" value="CAROUSEL" onchange="switchFormat('CAROUSEL')" class="text-purple-600 focus:ring-0">
                            <div>
                                <div class="font-bold text-slate-200">Carousel</div>
                                <div class="text-[10px] text-slate-500">Multi-Kartu Produk</div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Bagian File Upload Berdasarkan Format -->
                <!-- 1. Single Image Section -->
                <div id="formatImageSection" class="p-3 bg-slate-950/60 border border-slate-800 rounded-xl space-y-2">
                    <label class="block text-slate-300 font-semibold">Upload File Gambar</label>
                    <input type="file" name="image_file" accept="image/*" class="w-full text-slate-400 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-purple-600/20 file:text-purple-300 hover:file:bg-purple-600/30 cursor-pointer">
                    <div class="text-[10px] text-slate-500">Rekomendasi rasio: 1:1 (1080x1080px) atau 4:5 (1080x1350px). Maks 10MB.</div>
                </div>

                <!-- 2. Single Video Section -->
                <div id="formatVideoSection" class="hidden p-3 bg-slate-950/60 border border-slate-800 rounded-xl space-y-2">
                    <label class="block text-slate-300 font-semibold">Upload File Video</label>
                    <input type="file" name="video_file" accept="video/mp4,video/quicktime" class="w-full text-slate-400 file:mr-4 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-purple-600/20 file:text-purple-300 hover:file:bg-purple-600/30 cursor-pointer">
                    <div class="text-[10px] text-slate-500">Format MP4/MOV. Rekomendasi rasio: 9:16 (Reels/Stories) atau 1:1. Maks 50MB.</div>
                </div>

                <!-- 3. Carousel Section -->
                <div id="formatCarouselSection" class="hidden p-3 bg-slate-950/60 border border-slate-800 rounded-xl space-y-3">
                    <label class="block text-slate-300 font-semibold">Kartu Carousel (Multi-Produk)</label>
                    <div class="space-y-3">
                        @for($i = 0; $i < 3; $i++)
                        <div class="p-2.5 bg-slate-900 border border-slate-800 rounded-lg space-y-2">
                            <div class="font-bold text-slate-300">Kartu #{{ $i + 1 }}</div>
                            <div class="grid grid-cols-2 gap-2">
                                <input type="file" name="carousel_images[]" accept="image/*" class="text-[11px] text-slate-400">
                                <input type="text" name="carousel_headlines[]" placeholder="Judul Kartu {{ $i + 1 }}" class="bg-slate-950 border border-slate-800 rounded px-2 py-1 text-white">
                            </div>
                            <input type="url" name="carousel_links[]" placeholder="https://domainanda.com/produk-{{ $i + 1 }}" class="w-full bg-slate-950 border border-slate-800 rounded px-2 py-1 text-white text-[11px]">
                        </div>
                        @endfor
                    </div>
                </div>

                <!-- Teks Iklan (Copywriting) -->
                <div>
                    <label class="block text-slate-300 font-semibold mb-1">Teks Utama (Primary Text / Caption)</label>
                    <textarea name="primary_text" rows="3" placeholder="Tuliskan copywriting iklan Anda di sini (Hook, Value Proposition, Promo)..." class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-white placeholder-slate-600 focus:outline-none focus:border-purple-500"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">Headline (Judul Tebal Iklan)</label>
                        <input type="text" name="headline" placeholder="contoh: Diskon Spesial 50% Hari Ini" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-white placeholder-slate-600 focus:outline-none focus:border-purple-500">
                    </div>
                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">Deskripsi Opsional (Subheadline)</label>
                        <input type="text" name="description" placeholder="contoh: Gratis Ongkir Se-Indonesia" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-white placeholder-slate-600 focus:outline-none focus:border-purple-500">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">Call to Action (Tombol CTA)</label>
                        <select name="cta_type" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-purple-500 font-medium cursor-pointer">
                            <option value="SHOP_NOW">Beli Sekarang (SHOP_NOW)</option>
                            <option value="ORDER_NOW">Pesan Sekarang (ORDER_NOW)</option>
                            <option value="LEARN_MORE" selected>Pelajari Selengkapnya (LEARN_MORE)</option>
                            <option value="SIGN_UP">Daftar (SIGN_UP)</option>
                            <option value="CONTACT_US">Hubungi Kami (CONTACT_US)</option>
                            <option value="SEND_WHATSAPP_MESSAGE">Kirim Pesan WhatsApp</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">Landing Page / Website URL</label>
                        <input type="url" name="website_url" value="https://" required class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-purple-500 font-mono">
                    </div>
                </div>

                <div class="pt-3 flex items-center justify-end space-x-2 border-t border-slate-800">
                    <button type="button" onclick="document.getElementById('creativeStudioModal').classList.add('hidden')" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg font-semibold">
                        Batal
                    </button>
                    <button type="submit" class="px-5 py-2 bg-purple-600 hover:bg-purple-500 text-white rounded-lg font-semibold shadow-lg shadow-purple-950/40 flex items-center gap-2">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                        <span>Upload & Buat Iklan</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function switchFormat(format) {
            document.getElementById('formatImageSection').classList.add('hidden');
            document.getElementById('formatVideoSection').classList.add('hidden');
            document.getElementById('formatCarouselSection').classList.add('hidden');

            if (format === 'IMAGE') {
                document.getElementById('formatImageSection').classList.remove('hidden');
            } else if (format === 'VIDEO') {
                document.getElementById('formatVideoSection').classList.remove('hidden');
            } else if (format === 'CAROUSEL') {
                document.getElementById('formatCarouselSection').classList.remove('hidden');
            }
        }
    </script>

    <!-- Modal Input Token Manual (Tanpa OAuth / Tanpa HTTPS) -->
    <div id="manualTokenModal" class="hidden fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl max-w-lg w-full p-6 shadow-2xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 class="font-bold text-white text-base flex items-center gap-2">
                    <i class="fa-solid fa-key text-amber-400"></i>
                    Hubungkan Akun via Token (Tanpa HTTPS / OAuth)
                </h3>
                <button type="button" onclick="document.getElementById('manualTokenModal').classList.add('hidden')" class="text-slate-400 hover:text-white text-lg">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <p class="text-xs text-slate-400 leading-relaxed">
                Metode ini menggunakan <strong>System User Token</strong> atau <strong>Graph API Token</strong>. 
                <span class="text-emerald-400">100% Berfungsi tanpa perlu domain HTTPS atau redirect URI!</span>
            </p>

            <form action="{{ route('account.connect_manual') }}" method="POST" class="space-y-3 text-xs">
                @csrf
                <div>
                    <label class="block text-slate-300 font-semibold mb-1">Ad Account ID</label>
                    <input type="text" name="meta_account_id" placeholder="contoh: act_123456789 atau 123456789" required class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-white placeholder-slate-600 focus:outline-none focus:border-blue-500 font-mono">
                </div>

                <div>
                    <label class="block text-slate-300 font-semibold mb-1">Meta Access Token</label>
                    <textarea name="access_token" rows="3" placeholder="Tempelkan System User Token atau Graph API Token di sini..." required class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-white placeholder-slate-600 focus:outline-none focus:border-blue-500 font-mono"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">Target ROAS (x)</label>
                        <input type="number" step="0.1" name="target_roas" value="2.5" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-blue-500 font-mono">
                    </div>
                    <div>
                        <label class="block text-slate-300 font-semibold mb-1">Target CPA (IDR)</label>
                        <input type="number" step="1000" name="target_cpa" value="100000" class="w-full bg-slate-950 border border-slate-800 rounded-lg px-3 py-2 text-white focus:outline-none focus:border-blue-500 font-mono">
                    </div>
                </div>

                <div class="pt-3 flex items-center justify-end space-x-2 border-t border-slate-800">
                    <button type="button" onclick="document.getElementById('manualTokenModal').classList.add('hidden')" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg font-semibold">
                        Batal
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg font-semibold shadow-lg shadow-blue-900/30">
                        Simpan & Hubungkan
                    </button>
                </div>
            </form>
        </div>
    </div>

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

        <!-- Smart System Evaluation & Multi-Level Control (Campaign & AdSet Level) -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
            <div class="px-6 py-4 border-b border-slate-800/80 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-base font-bold text-white flex items-center gap-2">
                        <i class="fa-solid fa-brain text-indigo-400"></i>
                        Smart Evaluation Engine & Control Panel
                    </h2>
                    <p class="text-xs text-slate-400 mt-0.5">Analisis efektivitas performa penayangan dengan kontrol Level Campaign & Level AdSet</p>
                </div>
            </div>

            <!-- Level Switcher Tabs Header -->
            <div class="px-6 pt-3 pb-0 bg-slate-950/60 border-b border-slate-800 flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center space-x-1">
                    <button type="button" onclick="switchLevelView('adsets')" id="tabBtnAdsets" class="px-4 py-3 text-xs font-bold border-b-2 border-indigo-500 text-indigo-400 flex items-center gap-2 transition cursor-pointer">
                        <i class="fa-solid fa-layer-group"></i>
                        <span>Level AdSet & Penayangan</span>
                        <span class="px-2 py-0.5 rounded-full bg-indigo-500/20 text-indigo-300 text-[10px] font-mono">{{ count($evaluatedAdsets) }}</span>
                    </button>
                    <button type="button" onclick="switchLevelView('campaigns')" id="tabBtnCampaigns" class="px-4 py-3 text-xs font-semibold border-b-2 border-transparent text-slate-400 hover:text-white flex items-center gap-2 transition cursor-pointer">
                        <i class="fa-solid fa-bullhorn"></i>
                        <span>Level Campaign & Status Induk</span>
                        <span class="px-2 py-0.5 rounded-full bg-slate-800 text-slate-300 text-[10px] font-mono">{{ count($campaignList ?? []) }}</span>
                    </button>
                </div>

                <div class="pb-3 flex flex-wrap items-center gap-3">
                    <!-- Live Search Box -->
                    <div class="relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-2.5 text-slate-500 text-xs"></i>
                        <input type="text" id="adsetSearchInput" onkeyup="filterAdsetTable()" placeholder="Cari adset / campaign..." class="bg-slate-950 border border-slate-800 rounded-lg pl-8 pr-3 py-1.5 text-xs text-slate-200 placeholder-slate-500 focus:outline-none focus:border-blue-500 w-52 transition">
                    </div>

                    <!-- Filter Tab Status -->
                    <div class="flex items-center gap-1 bg-slate-950 p-1 rounded-lg border border-slate-800 text-xs font-medium">
                        <a href="{{ request()->fullUrlWithQuery(['filter_status' => 'all']) }}" class="px-2.5 py-1 rounded-md transition {{ ($filterStatus ?? 'all') === 'all' ? 'bg-indigo-600 text-white font-bold' : 'text-slate-400 hover:text-white' }}">Semua</a>
                        <a href="{{ request()->fullUrlWithQuery(['filter_status' => 'active']) }}" class="px-2.5 py-1 rounded-md transition {{ ($filterStatus ?? '') === 'active' ? 'bg-emerald-600 text-white font-bold' : 'text-slate-400 hover:text-white' }}">🟢 Hanya Aktif</a>
                        <a href="{{ request()->fullUrlWithQuery(['filter_status' => 'paused']) }}" class="px-2.5 py-1 rounded-md transition {{ ($filterStatus ?? '') === 'paused' ? 'bg-slate-800 text-white font-bold' : 'text-slate-400 hover:text-white' }}">⏸ Nonaktif (Paused)</a>
                    </div>
                </div>
            </div>

            <!-- VIEW 1: LEVEL ADSET & PENAYANGAN -->
            <div id="levelAdsetsView" class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-950/70 text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-800 text-[11px]">
                        <tr>
                            <th class="px-6 py-3.5">AdSet & Status Penayangan</th>
                            <th class="px-4 py-3.5">Health Score</th>
                            <th class="px-4 py-3.5">Status Efektivitas</th>
                            <th class="px-4 py-3.5">Spend & Konversi</th>
                            <th class="px-4 py-3.5">ROAS & CPA</th>
                            <th class="px-4 py-3.5">Hook (CTR & Freq)</th>
                            <th class="px-4 py-3.5">Diagnosa Smart</th>
                            <th class="px-6 py-3.5 text-right">Aksi Kontrol</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60" id="adsetTableBody">
                        @forelse($evaluatedAdsets as $item)
                        @php
                            $m = $item['metrics'];
                            $verdictColors = [
                                'SCALING_CANDIDATE' => 'bg-emerald-950/80 border-emerald-500/60 text-emerald-300',
                                'LOSING_MONEY' => 'bg-red-950/80 border-red-500/60 text-red-300',
                                'CREATIVE_FATIGUE' => 'bg-amber-950/80 border-amber-500/60 text-amber-300',
                                'HEALTHY_STABLE' => 'bg-blue-950/80 border-blue-500/60 text-blue-300',
                                'LEARNING_PHASE' => 'bg-slate-800/80 border-slate-600/60 text-slate-300',
                                'CAMPAIGN_PAUSED' => 'bg-amber-950/80 border-amber-500/60 text-amber-300',
                            ];
                            $vClass = $verdictColors[$item['verdict']] ?? 'bg-slate-800 border-slate-600 text-slate-300';
                            
                            $cModel = $item['model']->campaign;
                            $cStatus = $cModel?->status ?? 'ACTIVE';

                            $analysisPayload = [
                                'meta_id' => $item['meta_id'],
                                'name' => $item['name'],
                                'campaign_name' => $cModel?->name ?? 'Campaign',
                                'campaign_status' => $cStatus,
                                'status' => $item['status'],
                                'raw_adset_status' => $item['raw_adset_status'] ?? $item['status'],
                                'daily_budget' => (float) $item['current_daily_budget'],
                                'health_score' => $item['health_score'],
                                'verdict' => $item['verdict'],
                                'risk_level' => $item['risk_level'],
                                'metrics' => $item['metrics'],
                                'reasons' => $item['reasons'],
                                'recommended_actions' => $item['recommended_actions'],
                            ];
                        @endphp
                        <tr class="hover:bg-slate-800/30 transition adset-row" data-name="{{ strtolower($item['name'] . ' ' . ($cModel?->name ?? '')) }}">
                            <td class="px-6 py-3.5">
                                <div class="font-semibold text-white text-sm hover:text-indigo-400 transition cursor-pointer" onclick="openAnalysisModal(this.closest('tr').querySelector('.btn-analisa'))">
                                    {{ $item['name'] }}
                                </div>
                                <div class="text-[11px] text-slate-400 mt-1 flex flex-wrap items-center gap-2 font-mono">
                                    <!-- Badge Status Penayangan Efektif -->
                                    @if($item['status'] === 'ACTIVE')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                            🟢 TAYANG (ACTIVE)
                                        </span>
                                    @elseif($item['status'] === 'CAMPAIGN_PAUSED')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-500/15 text-amber-300 border border-amber-500/30" title="Penayangan terhenti karena Campaign induk PAUSED di Meta">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                                            ⏸ NONAKTIF (Campaign Paused)
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-800 text-slate-400 border border-slate-700">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-500"></span>
                                            ⏸ NONAKTIF (AdSet Paused)
                                        </span>
                                    @endif
                                    <span>•</span>
                                    <span>ID: {{ $item['meta_id'] }}</span>
                                    <span>•</span>
                                    <span class="text-slate-300 font-medium">Rp {{ number_format($item['current_daily_budget'], 0, ',', '.') }}/hari</span>
                                </div>

                                <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                    <!-- Status Level Campaign Induk -->
                                    <span class="text-[10px] bg-slate-800/90 border border-slate-700 text-slate-300 px-2 py-0.5 rounded-md font-medium flex items-center gap-1.5">
                                        <span>📁 {{ $cModel?->name ?? 'Campaign' }}</span>
                                        <span>•</span>
                                        @if($cStatus === 'ACTIVE')
                                            <span class="text-emerald-400 font-semibold">🟢 Campaign Aktif</span>
                                        @else
                                            <span class="text-amber-400 font-semibold">⏸ Campaign Nonaktif</span>
                                        @endif
                                    </span>

                                    @php
                                        $ads = $item['model']->ads ?? collect();
                                        $videoCount = $ads->filter(fn($ad) => ($ad->creative_payload['format'] ?? $ad->creative_payload['media_type'] ?? '') === 'VIDEO')->count();
                                        $imageCount = $ads->filter(fn($ad) => ($ad->creative_payload['format'] ?? $ad->creative_payload['media_type'] ?? '') === 'IMAGE')->count();
                                        $carouselCount = $ads->filter(fn($ad) => ($ad->creative_payload['format'] ?? '') === 'CAROUSEL')->count();
                                    @endphp
                                    @if($videoCount > 0)
                                        <span class="text-[10px] bg-purple-950/80 border border-purple-500/40 text-purple-300 px-1.5 py-0.5 rounded font-mono flex items-center gap-1">
                                            <i class="fa-solid fa-film"></i> {{ $videoCount }} Video
                                        </span>
                                    @endif
                                    @if($imageCount > 0)
                                        <span class="text-[10px] bg-sky-950/80 border border-sky-500/40 text-sky-300 px-1.5 py-0.5 rounded font-mono flex items-center gap-1">
                                            <i class="fa-solid fa-image"></i> {{ $imageCount }} Image
                                        </span>
                                    @endif
                                    @if($carouselCount > 0)
                                        <span class="text-[10px] bg-pink-950/80 border border-pink-500/40 text-pink-300 px-1.5 py-0.5 rounded font-mono flex items-center gap-1">
                                            <i class="fa-solid fa-layer-group"></i> {{ $carouselCount }} Carousel
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <div class="flex items-center space-x-2">
                                    <span class="font-bold text-sm {{ $item['health_score'] >= 80 ? 'text-emerald-400' : ($item['health_score'] < 40 ? 'text-red-400' : 'text-amber-400') }}">
                                        {{ $item['health_score'] }}/100
                                    </span>
                                </div>
                                <div class="w-20 bg-slate-800 rounded-full h-1.5 mt-1 overflow-hidden">
                                    <div class="h-1.5 rounded-full {{ $item['health_score'] >= 80 ? 'bg-emerald-500' : ($item['health_score'] < 40 ? 'bg-red-500' : 'bg-amber-500') }}" style="width: {{ $item['health_score'] }}%"></div>
                                </div>
                            </td>

                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[11px] font-bold border {{ $vClass }}">
                                    @if($item['verdict'] === 'SCALING_CANDIDATE')
                                        🚀 SIAP SCALE
                                    @elseif($item['verdict'] === 'LOSING_MONEY')
                                        🛑 BONCOS / CUT
                                    @elseif($item['verdict'] === 'CREATIVE_FATIGUE')
                                        ⚠️ FATIGUE
                                    @elseif($item['verdict'] === 'HEALTHY_STABLE')
                                        🟢 STABIL
                                    @elseif($item['verdict'] === 'CAMPAIGN_PAUSED')
                                        ⏸ CAMPAIGN PAUSED
                                    @else
                                        ⏳ {{ $item['verdict'] }}
                                    @endif
                                </span>
                            </td>

                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <div class="font-semibold text-white">Rp {{ number_format($m['spend'], 0, ',', '.') }}</div>
                                <div class="text-emerald-400 font-bold mt-0.5 text-xs">{{ $m['conversions'] }} Pembelian</div>
                            </td>

                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <div class="font-bold text-sm {{ $m['roas'] >= $m['target_roas'] ? 'text-emerald-400' : 'text-amber-400' }}">
                                    {{ $m['roas'] }}x ROAS
                                </div>
                                <div class="text-[11px] text-slate-400 mt-0.5">
                                    CPA: Rp {{ number_format($m['cpa'], 0, ',', '.') }}
                                </div>
                            </td>

                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-bold text-sm {{ $m['ctr'] >= 2.0 ? 'text-emerald-400' : ($m['ctr'] < 1.0 ? 'text-red-400' : 'text-slate-200') }}">
                                        {{ $m['ctr'] }}% CTR
                                    </span>
                                    @if(!empty($m['ctr_grade']))
                                        <span class="text-[9px] px-1.5 py-0.5 rounded font-bold uppercase {{ $m['ctr'] >= 2.0 ? 'bg-emerald-950/80 text-emerald-300 border border-emerald-500/30' : ($m['ctr'] < 1.0 ? 'bg-red-950/80 text-red-300 border border-red-500/30' : 'bg-slate-800 text-slate-300') }}">
                                            {{ $m['ctr_grade'] }}
                                        </span>
                                    @endif
                                </div>
                                <div class="text-[11px] text-slate-400 mt-1 flex items-center gap-1.5 font-mono">
                                    <span>Freq: <strong class="{{ $m['frequency'] > 2.8 ? 'text-amber-400' : 'text-white' }}">{{ $m['frequency'] }}x</strong></span>
                                    <span>•</span>
                                    <span>CVR: <strong class="text-indigo-300">{{ $m['cvr'] ?? 0 }}%</strong></span>
                                </div>
                                @if(isset($m['ctr_decay_ratio']) && $m['ctr_decay_ratio'] < 0.75)
                                    <div class="mt-1 text-[10px] text-amber-400 font-semibold flex items-center gap-1">
                                        <i class="fa-solid fa-triangle-exclamation"></i> Turun {{ round((1 - $m['ctr_decay_ratio']) * 100) }}% (Fatigue)
                                    </div>
                                @endif
                            </td>

                            <td class="px-4 py-3.5 max-w-xs">
                                <div class="text-[11px] text-slate-300 leading-snug line-clamp-2">
                                    {{ $item['reasons'][0] ?? 'Performa stabil sesuai target kriteria.' }}
                                </div>
                                @if(count($item['reasons']) > 1)
                                    <button type="button" onclick="openAnalysisModal(this.closest('tr').querySelector('.btn-analisa'))" class="text-[10px] text-indigo-400 hover:text-indigo-300 mt-1 flex items-center gap-1 font-medium transition cursor-pointer">
                                        <i class="fa-solid fa-circle-info text-[9px]"></i>
                                        <span>+{{ count($item['reasons']) - 1 }} temuan lainnya (klik analisa)</span>
                                    </button>
                                @endif
                            </td>

                            <td class="px-6 py-3.5 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end space-x-1.5">
                                    <!-- Tombol Analisa Data Lengkap (Clean json_encode tanpa double htmlspecialchars) -->
                                    <button type="button" 
                                        data-adset="{{ json_encode($analysisPayload) }}" 
                                        onclick="openAnalysisModal(this)" 
                                        class="btn-analisa px-2.5 py-1.5 bg-indigo-600/20 hover:bg-indigo-600/35 text-indigo-300 hover:text-white border border-indigo-500/30 rounded-lg text-xs font-semibold transition inline-flex items-center gap-1.5 shadow-sm cursor-pointer" 
                                        title="Lihat Analisa Data Lengkap & Conversion Funnel">
                                        <i class="fa-solid fa-chart-pie text-xs"></i>
                                        <span>Analisa</span>
                                    </button>

                                    @if($item['status'] === 'ACTIVE')
                                        <!-- Scale Up +20% -->
                                        <form action="{{ route('adset.scale', $item['meta_id']) }}" method="POST" class="inline">
                                            @csrf
                                            <input type="hidden" name="percentage" value="20">
                                            <button type="submit" class="px-2.5 py-1.5 bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-400 border border-emerald-500/30 rounded-lg text-xs font-semibold transition inline-flex items-center gap-1" title="Scale Budget +20%">
                                                <i class="fa-solid fa-arrow-trend-up text-[10px]"></i>
                                                <span>+20%</span>
                                            </button>
                                        </form>

                                        <!-- Pause Adset -->
                                        <form action="{{ route('adset.status', $item['meta_id']) }}" method="POST" class="inline">
                                            @csrf
                                            <input type="hidden" name="status" value="PAUSED">
                                            <button type="submit" class="px-2 py-1.5 bg-red-600/20 hover:bg-red-600/30 text-red-400 border border-red-500/30 rounded-lg text-xs font-semibold transition inline-flex items-center gap-1" title="Pause Adset">
                                                <i class="fa-solid fa-pause text-[10px]"></i>
                                                <span>Pause</span>
                                            </button>
                                        </form>
                                    @else
                                        <!-- Activate Adset -->
                                        <form action="{{ route('adset.status', $item['meta_id']) }}" method="POST" class="inline">
                                            @csrf
                                            <input type="hidden" name="status" value="ACTIVE">
                                            <button type="submit" class="px-2 py-1.5 bg-blue-600/20 hover:bg-blue-600/30 text-blue-400 border border-blue-500/30 rounded-lg text-xs font-semibold transition inline-flex items-center gap-1" title="Aktifkan Kembali">
                                                <i class="fa-solid fa-play text-[10px]"></i>
                                                <span>Aktifkan</span>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center py-10 text-slate-500">Belum ada adset yang disinkronkan.</td>
                        </tr>
                        @endforelse
                        <!-- Baris kosong saat filter search tidak ada hasil -->
                        <tr id="adsetSearchEmptyRow" style="display: none;">
                            <td colspan="8" class="text-center py-10 text-slate-500">
                                <div class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-slate-900 border border-slate-800 text-slate-400 mb-2">
                                    <i class="fa-solid fa-magnifying-glass text-sm"></i>
                                </div>
                                <p class="text-xs font-semibold text-slate-300">Tidak ada adset atau campaign yang cocok dengan pencarian</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- VIEW 2: LEVEL CAMPAIGN & STATUS INDUK -->
            <div id="levelCampaignsView" class="hidden overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-950/70 text-slate-400 uppercase tracking-wider font-semibold border-b border-slate-800 text-[11px]">
                        <tr>
                            <th class="px-6 py-3.5">Campaign & Info</th>
                            <th class="px-4 py-3.5">Status Level Campaign</th>
                            <th class="px-4 py-3.5">Objective Meta</th>
                            <th class="px-4 py-3.5">AdSets Terhubung</th>
                            <th class="px-4 py-3.5">Total Spend</th>
                            <th class="px-4 py-3.5">Total Revenue & ROAS</th>
                            <th class="px-6 py-3.5 text-right">Aksi Kontrol Campaign</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @forelse($campaignList ?? [] as $camp)
                        <tr class="hover:bg-slate-800/30 transition">
                            <td class="px-6 py-3.5">
                                <div class="font-semibold text-white text-sm">{{ $camp['name'] }}</div>
                                <div class="text-[11px] text-slate-400 mt-1 flex items-center gap-2 font-mono">
                                    <span>ID: {{ $camp['meta_id'] }}</span>
                                    @if($camp['daily_budget'])
                                        <span>•</span>
                                        <span class="text-slate-300 font-medium">Budget: Rp {{ number_format($camp['daily_budget'], 0, ',', '.') }}/hari</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                @if($camp['status'] === 'ACTIVE')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-bold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                        🟢 AKTIF (TAYANG)
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[11px] font-bold bg-amber-500/15 text-amber-400 border border-amber-500/30">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span>
                                        ⏸ NONAKTIF (PAUSED)
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded bg-slate-800 text-slate-300 font-mono text-[10px]">
                                    {{ $camp['objective'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <span class="font-bold text-white">{{ $camp['adsets_count'] }}</span>
                                <span class="text-slate-400 text-[11px]">AdSets</span>
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <div class="font-semibold text-white">Rp {{ number_format($camp['spend'], 0, ',', '.') }}</div>
                                <div class="text-emerald-400 font-bold mt-0.5 text-xs">{{ $camp['conversions'] }} Pembelian</div>
                            </td>
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <div class="font-bold text-sm {{ $camp['roas'] >= 2.5 ? 'text-emerald-400' : 'text-amber-400' }}">
                                    {{ $camp['roas'] }}x ROAS
                                </div>
                                <div class="text-[11px] text-slate-400 mt-0.5">
                                    Omset: Rp {{ number_format($camp['revenue'], 0, ',', '.') }}
                                </div>
                            </td>
                            <td class="px-6 py-3.5 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end space-x-2">
                                    <button type="button" onclick="filterByCampaign('{{ addslashes($camp['name']) }}')" class="px-2.5 py-1.5 bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-300 border border-indigo-500/30 rounded-lg text-xs font-semibold transition inline-flex items-center gap-1 cursor-pointer">
                                        <i class="fa-solid fa-eye text-[10px]"></i>
                                        <span>Lihat AdSets</span>
                                    </button>
                                    @if($camp['status'] === 'ACTIVE')
                                        <form action="{{ route('campaign.status', $camp['meta_id']) }}" method="POST" class="inline">
                                            @csrf
                                            <input type="hidden" name="status" value="PAUSED">
                                            <button type="submit" class="px-2.5 py-1.5 bg-red-600/20 hover:bg-red-600/30 text-red-400 border border-red-500/30 rounded-lg text-xs font-semibold transition inline-flex items-center gap-1 cursor-pointer" title="Pause Seluruh Campaign">
                                                <i class="fa-solid fa-pause text-[10px]"></i>
                                                <span>Pause</span>
                                            </button>
                                        </form>
                                    @else
                                        <form action="{{ route('campaign.status', $camp['meta_id']) }}" method="POST" class="inline">
                                            @csrf
                                            <input type="hidden" name="status" value="ACTIVE">
                                            <button type="submit" class="px-2.5 py-1.5 bg-blue-600/20 hover:bg-blue-600/30 text-blue-400 border border-blue-500/30 rounded-lg text-xs font-semibold transition inline-flex items-center gap-1 cursor-pointer" title="Aktifkan Campaign">
                                                <i class="fa-solid fa-play text-[10px]"></i>
                                                <span>Aktifkan</span>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-10 text-slate-500">Belum ada campaign yang disinkronkan.</td>
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

    <!-- Modal Analisa Data Lengkap & Conversion Funnel -->
    <div id="deepAnalysisModal" class="hidden fixed inset-0 z-50 bg-slate-950/80 backdrop-blur-sm flex items-center justify-center p-4 sm:p-6 overflow-y-auto">
        <div class="relative w-full max-w-4xl bg-slate-900 border border-slate-700/80 rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col my-auto">
            <!-- Modal Header -->
            <div class="px-6 py-4 bg-slate-950/90 border-b border-slate-800 flex items-center justify-between sticky top-0 z-10">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-purple-600 flex items-center justify-center text-white shadow-lg shadow-indigo-500/20">
                        <i class="fa-solid fa-chart-pie text-lg"></i>
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 id="modalAdsetName" class="text-base font-bold text-white truncate max-w-sm sm:max-w-md">Nama Adset</h3>
                            <span id="modalAdsetStatusBadge" class="px-2 py-0.5 rounded text-[10px] font-bold">ACTIVE</span>
                        </div>
                        <div class="flex items-center gap-2 text-xs text-slate-400 mt-0.5 font-mono">
                            <span id="modalCampaignName" class="text-indigo-400 font-sans font-medium">Campaign</span>
                            <span>•</span>
                            <span id="modalMetaId" class="text-slate-500">ID: -</span>
                            <span>•</span>
                            <span id="modalBudget" class="text-emerald-400">Budget: -</span>
                        </div>
                    </div>
                </div>
                <button type="button" onclick="closeAnalysisModal()" class="w-8 h-8 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white flex items-center justify-center transition cursor-pointer">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <!-- Modal Body (Scrollable) -->
            <div class="p-6 overflow-y-auto space-y-6 flex-1 text-slate-200">

                <!-- Banner Health & Verdict -->
                <div id="modalVerdictBanner" class="p-4 rounded-xl border flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="text-center bg-slate-950/80 px-4 py-2.5 rounded-xl border border-slate-800 min-w-[90px]">
                            <div class="text-[10px] uppercase tracking-wider text-slate-400 font-bold">Health Score</div>
                            <div id="modalHealthScore" class="text-2xl font-black text-emerald-400">0/100</div>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-slate-400 font-semibold">Kesimpulan Evaluasi:</span>
                                <span id="modalVerdictBadge" class="px-2 py-0.5 rounded text-xs font-bold uppercase tracking-wide">STATUS</span>
                            </div>
                            <div id="modalVerdictDesc" class="text-xs text-slate-300 mt-1 font-medium">Deskripsi status evaluasi.</div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 self-start sm:self-center">
                        <span class="text-xs text-slate-400">Tingkat Risiko:</span>
                        <span id="modalRiskLevelBadge" class="px-2.5 py-0.5 rounded text-[11px] font-bold uppercase bg-slate-800 text-slate-300 border border-slate-700">LOW</span>
                    </div>
                </div>

                <!-- 4 Grid KPI Card Metrik Penting -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <!-- Card 1: Finansial -->
                    <div class="bg-slate-950/60 border border-slate-800/80 rounded-xl p-3.5">
                        <div class="text-slate-400 text-[11px] font-semibold flex items-center justify-between">
                            <span>Total Biaya (Spend)</span>
                            <i class="fa-solid fa-wallet text-slate-500"></i>
                        </div>
                        <div id="modalSpend" class="text-base font-bold text-white mt-1">Rp 0</div>
                        <div class="mt-2 pt-2 border-t border-slate-800/60 text-[11px] flex justify-between text-slate-400">
                            <span>Total Omset:</span>
                            <span id="modalRevenue" class="font-semibold text-emerald-400">Rp 0</span>
                        </div>
                    </div>

                    <!-- Card 2: ROAS & Target -->
                    <div class="bg-slate-950/60 border border-slate-800/80 rounded-xl p-3.5">
                        <div class="text-slate-400 text-[11px] font-semibold flex items-center justify-between">
                            <span>ROAS Aktual</span>
                            <i class="fa-solid fa-chart-line text-slate-500"></i>
                        </div>
                        <div id="modalRoas" class="text-base font-bold text-white mt-1">0.00x</div>
                        <div class="mt-2 pt-2 border-t border-slate-800/60 text-[11px] flex justify-between text-slate-400">
                            <span>Target ROAS:</span>
                            <span id="modalTargetRoas" class="font-semibold text-slate-300">2.50x</span>
                        </div>
                    </div>

                    <!-- Card 3: Biaya Akuisisi (CPA) -->
                    <div class="bg-slate-950/60 border border-slate-800/80 rounded-xl p-3.5">
                        <div class="text-slate-400 text-[11px] font-semibold flex items-center justify-between">
                            <span>Cost Per Action (CPA)</span>
                            <i class="fa-solid fa-bullseye text-slate-500"></i>
                        </div>
                        <div id="modalCpa" class="text-base font-bold text-white mt-1">Rp 0</div>
                        <div class="mt-2 pt-2 border-t border-slate-800/60 text-[11px] flex justify-between text-slate-400">
                            <span>Target CPA:</span>
                            <span id="modalTargetCpa" class="font-semibold text-slate-300">Rp 0</span>
                        </div>
                    </div>

                    <!-- Card 4: Konversi Total -->
                    <div class="bg-slate-950/60 border border-slate-800/80 rounded-xl p-3.5">
                        <div class="text-slate-400 text-[11px] font-semibold flex items-center justify-between">
                            <span>Hasil Konversi</span>
                            <i class="fa-solid fa-bag-shopping text-slate-500"></i>
                        </div>
                        <div id="modalConversions" class="text-base font-bold text-emerald-400 mt-1">0 Pembelian</div>
                        <div class="mt-2 pt-2 border-t border-slate-800/60 text-[11px] flex justify-between text-slate-400">
                            <span>CVR (Klik ke Beli):</span>
                            <span id="modalCvr" class="font-semibold text-indigo-300">0%</span>
                        </div>
                    </div>
                </div>

                <!-- Conversion Funnel & Creative Diagnostic (3 Stages) -->
                <div class="bg-slate-950/70 border border-slate-800 rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3 border-b border-slate-800/80 pb-2">
                        <h4 class="text-xs font-bold text-white flex items-center gap-2">
                            <i class="fa-solid fa-filter text-indigo-400"></i>
                            3-Stage Conversion Funnel & Daya Tarik Kreatif
                        </h4>
                        <span class="text-[10px] text-slate-400 font-mono">Top $\rightarrow$ Bottom Funnel</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <!-- Stage 1: Awareness & Hook -->
                        <div class="bg-slate-900/80 border border-slate-800 p-3 rounded-lg flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between text-[11px] text-slate-400 font-medium mb-1">
                                    <span>1. Awareness (Tampilan)</span>
                                    <span class="text-xs text-sky-400"><i class="fa-solid fa-eye"></i></span>
                                </div>
                                <div id="modalImpressions" class="text-lg font-bold text-white">0</div>
                                <div class="text-[11px] text-slate-400 mt-1">Total impresi iklan yang disajikan Meta.</div>
                            </div>
                            <div class="mt-3 pt-2 border-t border-slate-800/80 text-[11px] text-slate-400 flex justify-between">
                                <span>Frekuensi Paparan:</span>
                                <span id="modalFrequency" class="font-semibold text-white">1.00x</span>
                            </div>
                        </div>

                        <!-- Stage 2: Engagement & CTR -->
                        <div class="bg-slate-900/80 border border-slate-800 p-3 rounded-lg flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between text-[11px] text-slate-400 font-medium mb-1">
                                    <span>2. Hook / Daya Tarik (Klik)</span>
                                    <span class="text-xs text-amber-400"><i class="fa-solid fa-arrow-pointer"></i></span>
                                </div>
                                <div class="flex items-baseline gap-2">
                                    <div id="modalClicks" class="text-lg font-bold text-white">0</div>
                                    <div class="text-xs font-semibold text-slate-400">(<span id="modalCtr" class="text-amber-400 font-bold">0%</span> CTR)</div>
                                </div>
                                <div id="modalCtrGrade" class="text-[10px] mt-1 inline-block px-1.5 py-0.5 rounded font-bold uppercase bg-slate-800 text-slate-300">GRADE</div>
                            </div>
                            <div class="mt-3 pt-2 border-t border-slate-800/80 text-[11px] text-slate-400 flex justify-between">
                                <span>Biaya per Klik (CPC):</span>
                                <span id="modalCpc" class="font-semibold text-white">Rp 0</span>
                            </div>
                        </div>

                        <!-- Stage 3: Conversion & Purchases -->
                        <div class="bg-slate-900/80 border border-slate-800 p-3 rounded-lg flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between text-[11px] text-slate-400 font-medium mb-1">
                                    <span>3. Closing / Pembelian</span>
                                    <span class="text-xs text-emerald-400"><i class="fa-solid fa-cart-shopping"></i></span>
                                </div>
                                <div id="modalFunnelConversions" class="text-lg font-bold text-emerald-400">0</div>
                                <div class="text-[11px] text-slate-400 mt-1">Hasil transaksi pembelian yang tercatat pixel Meta.</div>
                            </div>
                            <div class="mt-3 pt-2 border-t border-slate-800/80 text-[11px] text-slate-400 flex justify-between">
                                <span>Conversion Rate (CVR):</span>
                                <span id="modalFunnelCvr" class="font-semibold text-emerald-300">0%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Funnel Bottleneck Insight Box -->
                    <div id="modalFunnelInsight" class="mt-3 p-3 rounded-lg bg-indigo-950/40 border border-indigo-500/30 text-xs text-indigo-200 flex items-start gap-2.5">
                        <i class="fa-solid fa-lightbulb text-indigo-400 mt-0.5"></i>
                        <div id="modalFunnelInsightText">Menganalisis alur funnel...</div>
                    </div>
                </div>

                <!-- Creative Saturation / Decay Alert -->
                <div id="modalDecaySection" class="p-3.5 rounded-xl border border-amber-500/30 bg-amber-950/20 hidden">
                    <div class="flex items-center gap-2 text-xs font-bold text-amber-300">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span>Peringatan Kelelahan Materi Iklan (Creative Fatigue)</span>
                    </div>
                    <p id="modalDecayText" class="text-xs text-slate-300 mt-1">CTR adset ini mengalami penurunan performa dibandingkan 14 hari sebelumnya.</p>
                </div>

                <!-- Diagnosa Lengkap & Rekomendasi Aksi AI -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Temuan Diagnosa -->
                    <div class="bg-slate-950/70 border border-slate-800 rounded-xl p-4">
                        <h4 class="text-xs font-bold text-white mb-2.5 flex items-center gap-1.5">
                            <i class="fa-solid fa-magnifying-glass-chart text-purple-400"></i>
                            Temuan Diagnosa Smart Engine
                        </h4>
                        <ul id="modalReasonsList" class="space-y-2 text-xs text-slate-300">
                            <!-- populated via js -->
                        </ul>
                    </div>

                    <!-- Rekomendasi Aksi AI -->
                    <div class="bg-slate-950/70 border border-slate-800 rounded-xl p-4">
                        <h4 class="text-xs font-bold text-white mb-2.5 flex items-center gap-1.5">
                            <i class="fa-solid fa-wand-magic-sparkles text-emerald-400"></i>
                            Rekomendasi Aksi AI Agent
                        </h4>
                        <ul id="modalRecommendationsList" class="space-y-2 text-xs text-slate-300">
                            <!-- populated via js -->
                        </ul>
                    </div>
                </div>

            </div>

            <!-- Modal Footer with Quick Action Buttons -->
            <div class="px-6 py-4 bg-slate-950/90 border-t border-slate-800 flex flex-wrap items-center justify-between gap-3 sticky bottom-0 z-10">
                <button type="button" onclick="closeAnalysisModal()" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-lg text-xs font-semibold transition cursor-pointer">
                    Tutup
                </button>
                <div class="flex items-center gap-2" id="modalActionButtons">
                    <!-- Dynamic Quick Action Forms/Buttons -->
                </div>
            </div>
        </div>
    </div>

    <footer class="border-t border-slate-800 py-6 text-center text-xs text-slate-500">
        <p>&copy; {{ date('Y') }} Meta Ads Automation & AI Operations Platform. All rights reserved.</p>
    </footer>

    <script>
        function toggleDropdown(id) {
            const el = document.getElementById(id);
            if (el) {
                el.classList.toggle('hidden');
            }
        }

        window.addEventListener('click', function(e) {
            const createContainer = document.getElementById('dropdownCreateContainer');
            const createMenu = document.getElementById('dropdownCreateMenu');
            if (createContainer && !createContainer.contains(e.target)) {
                createMenu?.classList.add('hidden');
            }

            const accountContainer = document.getElementById('dropdownAccountContainer');
            const accountMenu = document.getElementById('dropdownAccountMenu');
            if (accountContainer && !accountContainer.contains(e.target)) {
                accountMenu?.classList.add('hidden');
            }

            const deepModal = document.getElementById('deepAnalysisModal');
            if (e.target === deepModal) {
                closeAnalysisModal();
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeAnalysisModal();
                document.getElementById('createCampaignModal')?.classList.add('hidden');
                document.getElementById('creativeStudioModal')?.classList.add('hidden');
                document.getElementById('manualTokenModal')?.classList.add('hidden');
            }
        });

        function formatRupiah(num) {
            if (num === null || num === undefined || isNaN(num)) return 'Rp 0';
            return 'Rp ' + Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }

        function switchLevelView(view) {
            const adsetsView = document.getElementById('levelAdsetsView');
            const campaignsView = document.getElementById('levelCampaignsView');
            const tabBtnAdsets = document.getElementById('tabBtnAdsets');
            const tabBtnCampaigns = document.getElementById('tabBtnCampaigns');

            if (view === 'adsets') {
                adsetsView?.classList.remove('hidden');
                campaignsView?.classList.add('hidden');
                tabBtnAdsets.className = "px-4 py-3 text-xs font-bold border-b-2 border-indigo-500 text-indigo-400 flex items-center gap-2 transition cursor-pointer";
                tabBtnCampaigns.className = "px-4 py-3 text-xs font-semibold border-b-2 border-transparent text-slate-400 hover:text-white flex items-center gap-2 transition cursor-pointer";
            } else {
                adsetsView?.classList.add('hidden');
                campaignsView?.classList.remove('hidden');
                tabBtnCampaigns.className = "px-4 py-3 text-xs font-bold border-b-2 border-indigo-500 text-indigo-400 flex items-center gap-2 transition cursor-pointer";
                tabBtnAdsets.className = "px-4 py-3 text-xs font-semibold border-b-2 border-transparent text-slate-400 hover:text-white flex items-center gap-2 transition cursor-pointer";
            }
        }

        function filterByCampaign(campaignName) {
            switchLevelView('adsets');
            const searchInput = document.getElementById('adsetSearchInput');
            if (searchInput) {
                searchInput.value = campaignName;
                filterAdsetTable();
            }
        }

        function filterAdsetTable() {
            const input = document.getElementById('adsetSearchInput');
            const query = (input ? input.value : '').toLowerCase().trim();
            const rows = document.querySelectorAll('.adset-row');
            let visibleCount = 0;

            rows.forEach(row => {
                const name = row.getAttribute('data-name') || '';
                if (name.includes(query)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            const emptyRow = document.getElementById('adsetSearchEmptyRow');
            if (emptyRow) {
                emptyRow.style.display = (visibleCount === 0 && rows.length > 0) ? '' : 'none';
            }
        }

        function openAnalysisModal(btn) {
            if (!btn) return;
            let raw = btn.getAttribute('data-adset');
            if (!raw) return;

            let data;
            try {
                // Defensive entity decoding: if raw was HTML escaped (&quot;)
                if (raw.includes('&quot;') || raw.includes('&amp;')) {
                    const txt = document.createElement('textarea');
                    txt.innerHTML = raw;
                    raw = txt.value;
                    if (raw.includes('&quot;')) {
                        txt.innerHTML = raw;
                        raw = txt.value;
                    }
                }
                data = JSON.parse(raw);
            } catch (err) {
                console.error("Gagal parsing data adset JSON:", err, raw);
                alert("Terjadi kesalahan memuat data analisa. Silakan coba lagi.");
                return;
            }

            const m = data.metrics || {};

            // Header AdSet & Campaign
            document.getElementById('modalAdsetName').textContent = data.name;
            const cStatusText = data.campaign_status === 'ACTIVE' ? '🟢 Campaign Aktif' : '⏸ Campaign Nonaktif';
            document.getElementById('modalCampaignName').textContent = '📁 ' + (data.campaign_name || 'Campaign') + ' (' + cStatusText + ')';
            document.getElementById('modalMetaId').textContent = 'ID: ' + data.meta_id;
            document.getElementById('modalBudget').textContent = 'Budget: ' + formatRupiah(data.daily_budget) + '/hari';

            const statusBadge = document.getElementById('modalAdsetStatusBadge');
            if (data.status === 'ACTIVE') {
                statusBadge.textContent = '🟢 TAYANG (ACTIVE)';
                statusBadge.className = 'px-2.5 py-0.5 rounded text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30';
            } else if (data.status === 'CAMPAIGN_PAUSED') {
                statusBadge.textContent = '⏸ NONAKTIF (Campaign Paused)';
                statusBadge.className = 'px-2.5 py-0.5 rounded text-[10px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30';
            } else {
                statusBadge.textContent = '⏸ NONAKTIF (' + (data.status || 'PAUSED') + ')';
                statusBadge.className = 'px-2.5 py-0.5 rounded text-[10px] font-bold bg-slate-800 text-slate-400 border border-slate-700';
            }

            // Health Score & Verdict
            const healthEl = document.getElementById('modalHealthScore');
            healthEl.textContent = data.health_score + '/100';
            if (data.health_score >= 80) {
                healthEl.className = 'text-2xl font-black text-emerald-400';
            } else if (data.health_score < 40) {
                healthEl.className = 'text-2xl font-black text-red-400';
            } else {
                healthEl.className = 'text-2xl font-black text-amber-400';
            }

            const verdictBadge = document.getElementById('modalVerdictBadge');
            const verdictDesc = document.getElementById('modalVerdictDesc');
            const banner = document.getElementById('modalVerdictBanner');

            const verdictLabels = {
                'SCALING_CANDIDATE': { 
                    label: '🚀 Siap Scale Up', 
                    desc: 'Performa sangat efektif dengan ROAS di atas target dan CPA sehat. Direkomendasikan naikkan budget.', 
                    border: 'border-emerald-500/50 bg-emerald-950/20', 
                    badge: 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40' 
                },
                'LOSING_MONEY': { 
                    label: '🛑 Boncos / Perlu Pause', 
                    desc: 'Pengeluaran melebihi batas toleransi tanpa hasil konversi memadai. Segera matikan atau perbaiki targeting/penawaran.', 
                    border: 'border-red-500/50 bg-red-950/20', 
                    badge: 'bg-red-500/20 text-red-300 border border-red-500/40' 
                },
                'CREATIVE_FATIGUE': { 
                    label: '⚠️ Creative Fatigue', 
                    desc: 'Frekuensi penayangan tinggi dan CTR menurun. Audiens mulai jenuh dengan visual/copy materi iklan saat ini.', 
                    border: 'border-amber-500/50 bg-amber-950/20', 
                    badge: 'bg-amber-500/20 text-amber-300 border border-amber-500/40' 
                },
                'HEALTHY_STABLE': { 
                    label: '🟢 Sehat & Stabil', 
                    desc: 'Adset berjalan normal dan konsisten memenuhi kriteria target performa.', 
                    border: 'border-blue-500/50 bg-blue-950/20', 
                    badge: 'bg-blue-500/20 text-blue-300 border border-blue-500/40' 
                },
                'CAMPAIGN_PAUSED': { 
                    label: '⏸ Campaign Nonaktif', 
                    desc: 'Penayangan adset ini terhenti otomatis karena Campaign induknya sedang PAUSED / Nonaktif di Meta Ads.', 
                    border: 'border-amber-500/50 bg-amber-950/20', 
                    badge: 'bg-amber-500/20 text-amber-300 border border-amber-500/40' 
                },
                'LEARNING_PHASE': { 
                    label: '⏳ Tahap Pembelajaran', 
                    desc: 'Iklan masih mengumpulkan volume data algoritma Meta untuk stabilisasi delivery.', 
                    border: 'border-slate-700 bg-slate-900', 
                    badge: 'bg-slate-800 text-slate-300 border border-slate-700' 
                }
            };

            const vInfo = verdictLabels[data.verdict] || { 
                label: data.verdict, 
                desc: 'Evaluasi performa berkala.', 
                border: 'border-slate-700 bg-slate-900', 
                badge: 'bg-slate-800 text-slate-300' 
            };
            verdictBadge.textContent = vInfo.label;
            verdictBadge.className = 'px-2.5 py-0.5 rounded text-xs font-bold uppercase tracking-wide ' + vInfo.badge;
            verdictDesc.textContent = vInfo.desc;
            banner.className = 'p-4 rounded-xl border flex flex-col sm:flex-row sm:items-center justify-between gap-4 ' + vInfo.border;

            document.getElementById('modalRiskLevelBadge').textContent = (data.risk_level || 'LOW') + ' RISK';

            // 4 KPI Cards
            document.getElementById('modalSpend').textContent = formatRupiah(m.spend || 0);
            document.getElementById('modalRevenue').textContent = formatRupiah(m.revenue || 0);
            document.getElementById('modalRoas').textContent = (m.roas || 0) + 'x';
            document.getElementById('modalTargetRoas').textContent = (m.target_roas || 2.5) + 'x';
            document.getElementById('modalCpa').textContent = formatRupiah(m.cpa || 0);
            document.getElementById('modalTargetCpa').textContent = formatRupiah(m.target_cpa || 100000);
            document.getElementById('modalConversions').textContent = (m.conversions || 0) + ' Pembelian';
            document.getElementById('modalCvr').textContent = (m.cvr || 0) + '%';

            // 3-Stage Funnel
            document.getElementById('modalImpressions').textContent = Number(m.impressions || 0).toLocaleString('id-ID');
            document.getElementById('modalFrequency').textContent = (m.frequency || 1.0).toFixed(2) + 'x';
            document.getElementById('modalClicks').textContent = Number(m.clicks || 0).toLocaleString('id-ID');
            document.getElementById('modalCtr').textContent = (m.ctr || 0) + '%';
            document.getElementById('modalCpc').textContent = formatRupiah(m.cpc || 0);
            document.getElementById('modalFunnelConversions').textContent = Number(m.conversions || 0).toLocaleString('id-ID');
            document.getElementById('modalFunnelCvr').textContent = (m.cvr || 0) + '%';

            // CTR Grade
            const ctrGradeEl = document.getElementById('modalCtrGrade');
            ctrGradeEl.textContent = m.ctr_grade || 'STANDAR';
            if ((m.ctr || 0) >= 2.0) {
                ctrGradeEl.className = 'text-[10px] mt-1 inline-block px-2 py-0.5 rounded font-bold uppercase bg-emerald-950/80 text-emerald-300 border border-emerald-500/30';
            } else if ((m.ctr || 0) < 1.0) {
                ctrGradeEl.className = 'text-[10px] mt-1 inline-block px-2 py-0.5 rounded font-bold uppercase bg-red-950/80 text-red-300 border border-red-500/30';
            } else {
                ctrGradeEl.className = 'text-[10px] mt-1 inline-block px-2 py-0.5 rounded font-bold uppercase bg-slate-800 text-slate-300 border border-slate-700';
            }

            // Funnel Insight
            const insightText = document.getElementById('modalFunnelInsightText');
            if (data.status === 'CAMPAIGN_PAUSED') {
                insightText.innerHTML = '<strong class="text-amber-300">⏸ Status Campaign Induk Nonaktif:</strong> Penayangan iklan terhenti di tingkat Campaign Meta. Silakan aktifkan Campaign induk pada tab "Level Campaign" agar adset ini dapat mulai menayangkan iklan kembali.';
            } else if ((m.ctr || 0) >= 1.5 && (m.cvr || 0) < 1.0 && (m.clicks || 0) >= 20) {
                insightText.innerHTML = '<strong class="text-amber-300">⚠️ Indikasi Landing Page Bottleneck:</strong> Hook visual & materi iklan sangat menarik (CTR tinggi ' + m.ctr + '%), namun konversi landing page rendah (' + m.cvr + '%). Periksa kecepatan loading halaman, relevansi copy headline, atau kemudahan proses checkout.';
            } else if ((m.ctr || 0) < 1.0 && (m.impressions || 0) >= 500) {
                insightText.innerHTML = '<strong class="text-red-300">⚠️ Hook Kurang Kuat:</strong> Rasio klik audiens rendah (' + m.ctr + '%). Penonton mengabaikan iklan. Disarankan membuat variasi thumbnail baru atau video 3 detik pertama yang lebih memikat (Creative Studio).';
            } else if ((m.roas || 0) >= (m.target_roas || 2.5)) {
                insightText.innerHTML = '<strong class="text-emerald-300">🚀 Funnel Sangat Prima:</strong> Alur dari impresi, klik, hingga pembelian menghasilkan ROAS ' + m.roas + 'x (Target ' + m.target_roas + 'x). Adset ini sangat layak mendapatkan suntikan budget lebih tinggi!';
            } else {
                insightText.innerHTML = '<strong class="text-slate-300">ℹ️ Kondisi Funnel:</strong> Funnel berjalan dalam batas wajar. Evaluasi berkala seiring pertumbuhan volume transaksi.';
            }

            // Creative Decay
            const decaySection = document.getElementById('modalDecaySection');
            const decayText = document.getElementById('modalDecayText');
            if (m.ctr_decay_ratio && m.ctr_decay_ratio < 0.75) {
                decaySection.classList.remove('hidden');
                decayText.textContent = `CTR adset ini menurun ${Math.round((1 - m.ctr_decay_ratio) * 100)}% dibandingkan riwayat 14 hari sebelumnya, seiring kenaikan frekuensi (${m.frequency || 1}x). Segera tambahkan materi iklan baru di Creative Studio.`;
            } else {
                decaySection.classList.add('hidden');
            }

            // Reasons List
            const reasonsList = document.getElementById('modalReasonsList');
            reasonsList.innerHTML = '';
            (data.reasons || []).forEach(r => {
                const li = document.createElement('li');
                li.className = 'flex items-start gap-2 text-slate-300';
                li.innerHTML = '<span class="text-indigo-400 mt-0.5">•</span><span>' + r + '</span>';
                reasonsList.appendChild(li);
            });

            // Recommendations List
            const recList = document.getElementById('modalRecommendationsList');
            recList.innerHTML = '';
            (data.recommended_actions || []).forEach(action => {
                const li = document.createElement('li');
                li.className = 'flex items-start gap-2 text-slate-300';
                let actionDesc = action;
                if (action === 'ACTIVATE_CAMPAIGN_FIRST') actionDesc = '▶ Aktifkan Campaign induk terlebih dahulu melalui tab Level Campaign agar adset ini bisa tayang.';
                if (action === 'SCALE_BUDGET_20') actionDesc = '🚀 Naikkan budget harian sebesar +20% secara bertahap.';
                if (action === 'KILL_PAUSE') actionDesc = '🛑 Matikan / Pause adset ini segera untuk menghentikan pemborosan biaya (boncos).';
                if (action === 'REFRESH_CREATIVES') actionDesc = '🎨 Tambahkan materi iklan baru (Creative Studio) karena audiens jenuh.';
                if (action === 'MAINTAIN') actionDesc = '✅ Pertahankan konfigurasi saat ini dan amati pertumbuhan metrik harian.';
                li.innerHTML = '<span class="text-emerald-400 mt-0.5"><i class="fa-solid fa-check text-[10px]"></i></span><span>' + actionDesc + '</span>';
                recList.appendChild(li);
            });

            // Action Buttons
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const actionsDiv = document.getElementById('modalActionButtons');
            actionsDiv.innerHTML = '';

            if (data.status === 'ACTIVE') {
                // Scale button form
                const scaleForm = document.createElement('form');
                scaleForm.action = `/adset/${data.meta_id}/scale`;
                scaleForm.method = 'POST';
                scaleForm.className = 'inline';
                scaleForm.innerHTML = `
                    <input type="hidden" name="_token" value="${csrfToken}">
                    <input type="hidden" name="percentage" value="20">
                    <button type="submit" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-xs font-bold transition flex items-center gap-1.5 shadow-sm cursor-pointer">
                        <i class="fa-solid fa-arrow-trend-up"></i> +20% Scale Budget
                    </button>
                `;
                actionsDiv.appendChild(scaleForm);

                // Pause button form
                const pauseForm = document.createElement('form');
                pauseForm.action = `/adset/${data.meta_id}/status`;
                pauseForm.method = 'POST';
                pauseForm.className = 'inline';
                pauseForm.innerHTML = `
                    <input type="hidden" name="_token" value="${csrfToken}">
                    <input type="hidden" name="status" value="PAUSED">
                    <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-500 text-white rounded-lg text-xs font-bold transition flex items-center gap-1.5 shadow-sm cursor-pointer">
                        <i class="fa-solid fa-pause"></i> Pause Adset
                    </button>
                `;
                actionsDiv.appendChild(pauseForm);
            } else {
                // If campaign is paused, offer campaign activate button
                if (data.status === 'CAMPAIGN_PAUSED' && data.meta_campaign_id) {
                    const campActForm = document.createElement('form');
                    campActForm.action = `/campaign/${data.meta_campaign_id}/status`;
                    campActForm.method = 'POST';
                    campActForm.className = 'inline';
                    campActForm.innerHTML = `
                        <input type="hidden" name="_token" value="${csrfToken}">
                        <input type="hidden" name="status" value="ACTIVE">
                        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-lg text-xs font-bold transition flex items-center gap-1.5 shadow-sm cursor-pointer">
                            <i class="fa-solid fa-bullhorn"></i> Aktifkan Campaign Induk
                        </button>
                    `;
                    actionsDiv.appendChild(campActForm);
                }

                // Activate adset button form
                const activateForm = document.createElement('form');
                activateForm.action = `/adset/${data.meta_id}/status`;
                activateForm.method = 'POST';
                activateForm.className = 'inline';
                activateForm.innerHTML = `
                    <input type="hidden" name="_token" value="${csrfToken}">
                    <input type="hidden" name="status" value="ACTIVE">
                    <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg text-xs font-bold transition flex items-center gap-1.5 shadow-sm cursor-pointer">
                        <i class="fa-solid fa-play"></i> Aktifkan Adset
                    </button>
                `;
                actionsDiv.appendChild(activateForm);
            }

            document.getElementById('deepAnalysisModal').classList.remove('hidden');
        }

        function closeAnalysisModal() {
            document.getElementById('deepAnalysisModal').classList.add('hidden');
        }
    </script>
</body>
</html>
