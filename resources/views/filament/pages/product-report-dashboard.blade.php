<x-filament-panels::page>
    @php
        $maxQty = collect($topRows)->max('qty') ?: 1;
    @endphp

    <style>
        /* ── Layout ─────────────────────────────────────────────── */
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #f1f5f9;
            padding-bottom: 16px;
            margin-bottom: 24px;
        }
        .report-header-title h2 {
            font-size: 22px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.025em;
        }
        .report-header-title p {
            font-size: 13px;
            color: #64748b;
            margin-top: 2px;
        }

        /* ── Stat cards ─────────────────────────────────────────── */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        @media (min-width: 640px)  { .stat-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (min-width: 1024px) { .stat-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); } }

        .stat-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,.02), 0 2px 8px rgba(0,0,0,.04);
            position: relative;
            overflow: hidden;
            transition: transform .2s, box-shadow .2s;
        }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0,0,0,.05); }
        .stat-card::before { content:''; position:absolute; top:0; left:0; width:4px; height:100%; }

        .stat-card-teal::before    { background: #14b8a6; }
        .stat-card-indigo::before  { background: #6366f1; }
        .stat-card-emerald::before { background: #10b981; }
        .stat-card-rose::before    { background: #f43f5e; }

        .stat-icon-wrapper {
            display: flex; align-items: center; justify-content: center;
            width: 42px; height: 42px; border-radius: 10px; margin-bottom: 12px;
        }
        .stat-icon-teal    { background: #f0fdfa; color: #14b8a6; }
        .stat-icon-indigo  { background: #eef2ff; color: #6366f1; }
        .stat-icon-emerald { background: #ecfdf5; color: #10b981; }
        .stat-icon-rose    { background: #fff1f2; color: #f43f5e; }

        .stat-label    { font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: .05em; }
        .stat-value    { font-size: 18px; font-weight: 800; color: #0f172a; margin-top: 4px; line-height: 1.2; word-break: break-word; }
        .stat-subtitle { font-size: 11px; color: #94a3b8; margin-top: 6px; }

        /* ── Cards ──────────────────────────────────────────────── */
        .card-panel {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,.02), 0 2px 8px rgba(0,0,0,.04);
            overflow: hidden;
        }
        .card-panel-header {
            padding: 16px 24px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }
        .card-panel-title {
            font-size: 15px; font-weight: 700; color: #0f172a;
            display: flex; align-items: center; gap: 8px;
        }

        /* ── Progress bar ───────────────────────────────────────── */
        .progress-bar-container {
            width: 100%; background-color: #f1f5f9; border-radius: 9999px;
            height: 6px; overflow: hidden; margin-top: 4px;
        }
        .progress-bar-fill {
            height: 100%; border-radius: 9999px;
            background: linear-gradient(90deg, #10b981, #34d399);
        }

        /* ── Buttons ────────────────────────────────────────────── */
        .btn-print {
            display: inline-flex; align-items: center; gap: 8px;
            background: #0f172a; color: white; padding: 8px 16px;
            border-radius: 10px; font-size: 13px; font-weight: 600;
            border: none; cursor: pointer;
            transition: background .2s, transform .1s;
            box-shadow: 0 4px 6px -1px rgba(15,23,42,.15);
        }
        .btn-print:hover { background: #1e293b; transform: translateY(-1px); }

        /* ── Period badges ──────────────────────────────────────── */
        .badge-period {
            font-weight: 700; font-size: 10px; padding: 3px 8px;
            border-radius: 6px; text-transform: uppercase;
        }
        .badge-period-harian   { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .badge-period-mingguan { background: #f5f3ff; color: #6d28d9; border: 1px solid #ddd6fe; }
        .badge-period-bulanan  { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
        .badge-period-semua    { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }

        /* ── Sticky table header ─────────────────────────────────── */
        .sticky-header th {
            position: sticky; top: 0;
            background: #f8fafc !important;
            z-index: 10;
            box-shadow: inset 0 -1px 0 #e2e8f0;
        }

        /* ── Print ──────────────────────────────────────────────── */
        @media print {
            body { background: white !important; color: black !important; }
            .fi-sidebar, .fi-topbar, .no-print, .btn-print, .fi-breadcrumbs { display: none !important; }
            .fi-main { padding: 0 !important; margin: 0 !important; }
            .stat-card, .card-panel {
                border: 1px solid #cbd5e1 !important;
                box-shadow: none !important;
                background: white !important;
                page-break-inside: avoid;
            }
        }
    </style>

    <div class="px-4 py-4 md:px-6 md:py-6 lg:px-8 lg:py-8">
        <div id="printReportArea" class="space-y-6">

            {{-- Print-only header --}}
            <div class="hidden print:block border-b-2 border-slate-300 pb-4 mb-6">
                <div class="flex justify-between items-end">
                    <div>
                        <h2 class="text-2xl font-bold text-slate-800">Laporan Kinerja & Analisis Produk</h2>
                        <p class="text-sm text-slate-500 mt-1">Dokumen formal statistik produk terlaris, kuantitas penjualan, dan kontribusi omzet.</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-semibold text-slate-700">
                            Toko: {{ $storeId ? ($stores[$storeId] ?? 'Tidak Dikenal') : 'Semua Toko' }}
                        </p>
                        <p class="text-xs text-slate-400 mt-1">Dicetak: {{ now()->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
            </div>

            {{-- Screen header with filters --}}
            <div class="report-header no-print">
                <div class="report-header-title">
                    <h2>Laporan Kinerja & Analisis Produk</h2>
                    <p>Statistik produk terlaris, kuantitas penjualan, dan kontribusi omzet.</p>
                </div>
                <div class="flex items-center gap-3 flex-wrap">
                    {{-- Store filter --}}
                    <div class="w-52">
                        <x-filament::input.wrapper>
                            <x-filament::input.select wire:model.live="storeId" style="font-weight:600;color:#1e293b;">
                                <option value="">✨ Semua Toko</option>
                                @foreach($stores as $id => $name)
                                    <option value="{{ $id }}">🏪 {{ $name }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </div>

                    {{-- Start Date Filter --}}
                    <div class="w-40">
                        <x-filament::input.wrapper>
                            <x-filament::input type="date" wire:model.live="startDate" style="font-weight: 600; color: #1e293b;" />
                        </x-filament::input.wrapper>
                    </div>

                    {{-- End Date Filter --}}
                    <div class="w-40">
                        <x-filament::input.wrapper>
                            <x-filament::input type="date" wire:model.live="endDate" style="font-weight: 600; color: #1e293b;" />
                        </x-filament::input.wrapper>
                    </div>

                    <button type="button" onclick="window.print()" class="btn-print">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:16px;height:16px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.89l-1.92-1.92L2.88 13.89M17.28 13.89l1.92-1.92 1.92 1.92M9 19.5h6a2.25 2.25 0 002.25-2.25V15M9 19.5a2.25 2.25 0 01-2.25-2.25V15m12 4.5a2.25 2.25 0 002.25-2.25V15m-15 0h15M9 4.5h6a2.25 2.25 0 012.25 2.25V9M9 4.5A2.25 2.25 0 006.75 6.75V9m10.5-2.25A2.25 2.25 0 0119.5 9m0 0v6M4.5 9v6M9 9h6" />
                        </svg>
                        Cetak Laporan (PDF)
                    </button>
                </div>
            </div>

            {{-- Executive Stat Cards --}}
            <div class="stat-grid">
                {{-- Total Kuantitas Terjual --}}
                <div class="stat-card stat-card-indigo">
                    <div class="stat-icon-wrapper stat-icon-indigo">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width:20px;height:20px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                        </svg>
                    </div>
                    <div class="stat-label">Total Produk Terjual</div>
                    <div class="stat-value">{{ number_format($totalQtySold, 0, ',', '.') }} Unit</div>
                    <div class="stat-subtitle">Akumulasi kuantitas (periode dipilih)</div>
                </div>

                {{-- Produk Terlaris --}}
                <div class="stat-card stat-card-emerald">
                    <div class="stat-icon-wrapper stat-icon-emerald">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width:20px;height:20px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0112 21 8.25 8.25 0 016.038 7.048 8.287 8.287 0 009 9.6a8.983 8.983 0 013.361-6.867 8.21 8.21 0 003 2.48z" />
                        </svg>
                    </div>
                    <div class="stat-label">Produk Terlaris</div>
                    <div class="stat-value text-emerald-600 truncate" title="{{ $topProductName }}">{{ $topProductName }}</div>
                    <div class="stat-subtitle">Terjual: <strong>{{ number_format($topProductCount, 0, ',', '.') }} Unit</strong></div>
                </div>

                {{-- Variasi Produk --}}
                <div class="stat-card stat-card-teal">
                    <div class="stat-icon-wrapper stat-icon-teal">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width:20px;height:20px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581a1.464 1.464 0 002.07 0l4.319-4.319a1.464 1.464 0 000-2.07l-9.581-9.581a2.25 2.25 0 00-1.591-.659z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" />
                        </svg>
                    </div>
                    <div class="stat-label">Variasi Produk Terjual</div>
                    <div class="stat-value">{{ number_format($totalUniqueProducts, 0, ',', '.') }} Jenis</div>
                    <div class="stat-subtitle">Produk aktif bertransaksi</div>
                </div>

                {{-- Periode aktif --}}
                <div class="stat-card stat-card-rose">
                    <div class="stat-icon-wrapper stat-icon-rose">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width:20px;height:20px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                        </svg>
                    </div>
                    <div class="stat-label">Filter Periode Aktif</div>
                    <div class="stat-value text-rose-600" style="font-size: 13px; font-weight: 700; word-break: keep-all;">
                        {{ \Carbon\Carbon::parse($startDate)->format('d/m/y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d/m/y') }}
                    </div>
                    <div class="stat-subtitle">
                        Toko: {{ $storeId ? ($stores[$storeId] ?? '—') : 'Semua Toko' }}
                    </div>
                </div>
            </div>

            {{-- Horizontal Chart --}}
            <div class="card-panel" wire:ignore>
                <div class="card-panel-header">
                    <div class="card-panel-title">
                        <span style="color:#10b981;">📊</span> Grafik Analisis 10 Produk Terlaris
                    </div>
                </div>
                <div class="p-6">
                    <canvas id="topProductChart" height="110"></canvas>
                </div>
            </div>

            {{-- Product Ranks Table --}}
            <div class="card-panel">
                {{-- Card header with per-page selector --}}
                <div class="card-panel-header">
                    <div class="card-panel-title">
                        <span>🏆</span> Klasifikasi & Ringkasan Produk Terpopuler
                    </div>
                    <div class="flex items-center gap-3 no-print">
                        <span class="text-xs text-gray-500 font-medium">Tampilkan:</span>
                        <div class="flex items-center gap-1">
                            @foreach([10, 20, 50, 100] as $opt)
                                <button
                                    wire:click="$set('perPage', {{ $opt }})"
                                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all
                                           {{ $perPage == $opt
                                              ? 'bg-emerald-600 text-white shadow-sm'
                                              : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}"
                                >{{ $opt }}</button>
                            @endforeach
                        </div>
                        <span class="text-xs text-gray-400">baris</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full table-auto text-sm">
                        <thead class="bg-gray-50 border-b sticky-header">
                            <tr>
                                <th class="px-6 py-3.5 text-left font-bold text-gray-600 text-xs uppercase tracking-wide w-28">Periode</th>
                                <th class="px-6 py-3.5 text-left font-bold text-gray-600 text-xs uppercase tracking-wide">Nama Produk</th>
                                <th class="px-6 py-3.5 text-right font-bold text-gray-600 text-xs uppercase tracking-wide w-36">Kuantitas Terjual</th>
                                <th class="px-6 py-3.5 text-left font-bold text-gray-600 text-xs uppercase tracking-wide w-48">Visualisasi</th>
                                <th class="px-6 py-3.5 text-right font-bold text-gray-600 text-xs uppercase tracking-wide">Omzet Kotor</th>
                                <th class="px-6 py-3.5 text-center font-bold text-gray-600 text-xs uppercase tracking-wide w-20 no-print">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($topRows as $row)
                                @php
                                    $percentage = ($row['qty'] / $maxQty) * 100;
                                    $badgeClass = 'badge-period-bulanan';
                                @endphp
                                <tr class="hover:bg-emerald-50/40 transition-colors">
                                    <td class="px-6 py-4">
                                        <span class="badge-period {{ $badgeClass }}">
                                            {{ $row['periode'] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 font-bold text-gray-800 text-sm">{{ $row['product_name'] }}</td>
                                    <td class="px-6 py-4 text-right font-extrabold text-gray-700 text-sm">
                                        {{ number_format($row['qty'], 0, ',', '.') }}
                                        <span class="text-gray-400 font-medium text-xs ml-1">{{ strtoupper($row['unit'] ?? 'PCS') }}</span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="progress-bar-container">
                                            <div class="progress-bar-fill" style="width: {{ $percentage }}%;"></div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right font-extrabold text-emerald-600 text-sm">Rp {{ number_format($row['omzet'], 0, ',', '.') }}</td>
                                    <td class="px-6 py-4 text-center no-print">
                                        <button
                                            wire:click="showProductSalesDetail({{ $row['product_id'] }})"
                                            class="px-2.5 py-1 rounded bg-blue-50 text-blue-700 hover:bg-blue-100 text-xs font-bold transition-all border border-blue-200">
                                            Detail
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                                        <div class="flex flex-col items-center gap-2">
                                            <span class="text-3xl">📭</span>
                                            <span class="font-medium">Belum ada data transaksi produk terekam</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Table footer --}}
                <div class="px-6 py-3 border-t border-gray-100 bg-gray-50/50 flex items-center justify-between no-print">
                    <span class="text-xs text-gray-400">
                        Menampilkan <strong class="text-gray-600">{{ count($topRows) }}</strong> produk
                    </span>
                    <span class="text-xs text-gray-400">Batas: {{ $perPage }} baris</span>
                </div>
            </div>

        </div>{{-- end printReportArea --}}
    </div>

    @once
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @endonce

    @php
    $productChartData = [
        'labels' => $topProductLabels ?? [],
        'qty'    => $topProductQty    ?? [],
    ];
    @endphp

    <script>
        window.__productChartData = @json($productChartData);
    </script>

    <script>
        (() => {
            let topChart = null;

            function getChartData() {
                return window.__productChartData ?? { labels: [], qty: [] };
            }

            function renderChart() {
                const el = document.getElementById('topProductChart');
                if (!el || typeof Chart === 'undefined') return;

                const { labels, qty } = getChartData();
                if (topChart) topChart.destroy();

                topChart = new Chart(el, {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [{
                            label: 'Kuantitas Terjual',
                            data: qty,
                            backgroundColor: '#10b981',
                            borderRadius: 6,
                            barPercentage: 0.6
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        plugins: { legend: { display: false } },
                        scales: {
                            x: { grid: { color: '#f1f5f9' }, ticks: { stepSize: 1 } },
                            y: { grid: { display: false } }
                        }
                    }
                });
            }

            document.addEventListener('DOMContentLoaded', renderChart);
            document.addEventListener('livewire:navigated', renderChart);
            document.addEventListener('product-data-updated', renderChart);
        })();
    </script>

    {{-- Product Sales Detail Modal --}}
    @if($selectedProductDetails !== null)
    <div class="modal-backdrop no-print" wire:click.self="closeProductSalesDetail" style="position: fixed; inset: 0; background: rgba(15, 23, 42, 0.55); backdrop-filter: blur(4px); z-index: 9998; display: flex; align-items: center; justify-content: center; padding: 24px;">
        <div class="modal-box" style="background: white; border-radius: 20px; box-shadow: 0 25px 60px -10px rgba(0,0,0,0.25), 0 10px 25px -5px rgba(0,0,0,0.1); width: 100%; max-width: 680px; max-height: 85vh; overflow: hidden; display: flex; flex-direction: column;">
            <div class="modal-header" style="padding: 20px 24px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: flex-start; background: #f8fafc; border-radius: 20px 20px 0 0; flex-shrink: 0;">
                <div>
                    <div class="text-xs font-semibold text-blue-500 uppercase tracking-widest mb-1" style="color: #3b82f6; font-weight: 700;">📦 Detail Penjualan Produk</div>
                    <div class="font-extrabold text-lg text-gray-900" style="font-size: 1.125rem; font-weight: 800; color: #0f172a;">{{ $selectedProductName }}</div>
                    <div class="text-xs text-gray-400 mt-1" style="color: #94a3b8;">
                        Rentang: {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} s/d {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
                    </div>
                </div>
                <button
                    wire:click="closeProductSalesDetail"
                    class="btn-close-modal ml-4 flex-shrink-0" style="display: inline-flex; align-items: center; gap: 6px; background: #fee2e2; color: #dc2626; border: none; padding: 7px 14px; border-radius: 8px; font-size: 12px; font-weight: 700; cursor: pointer;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width:14px;height:14px;"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    Tutup
                </button>
            </div>

            <div class="modal-body max-h-[350px]" style="overflow-y: auto; flex: 1;">
                <table class="w-full table-auto text-sm">
                    <thead class="bg-gray-50 border-b sticky-header" style="position: sticky; top: 0; background: #f8fafc; z-index: 10; box-shadow: inset 0 -1px 0 #e2e8f0;">
                        <tr>
                            <th class="px-6 py-3.5 text-left font-bold text-gray-600 text-xs uppercase tracking-wide" style="padding: 14px 24px; font-weight: 700; color: #4b5563;">Struk</th>
                            <th class="px-6 py-3.5 text-left font-bold text-gray-600 text-xs uppercase tracking-wide" style="padding: 14px 24px; font-weight: 700; color: #4b5563;">Tanggal</th>
                            <th class="px-6 py-3.5 text-left font-bold text-gray-600 text-xs uppercase tracking-wide" style="padding: 14px 24px; font-weight: 700; color: #4b5563;">Toko</th>
                            <th class="px-6 py-3.5 text-right font-bold text-gray-600 text-xs uppercase tracking-wide" style="padding: 14px 24px; font-weight: 700; color: #4b5563;">Jumlah</th>
                            <th class="px-6 py-3.5 text-right font-bold text-gray-600 text-xs uppercase tracking-wide" style="padding: 14px 24px; font-weight: 700; color: #4b5563;">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($selectedProductDetails as $det)
                        <tr class="hover:bg-blue-50/30 transition-colors">
                            <td class="px-6 py-4 font-semibold text-blue-600" style="padding: 16px 24px; font-weight: 600; color: #2563eb;">{{ $det['code'] }}</td>
                            <td class="px-6 py-4 text-gray-500" style="padding: 16px 24px; color: #6b7280;">{{ $det['time'] }}</td>
                            <td class="px-6 py-4 text-gray-700 font-medium" style="padding: 16px 24px; color: #374151;">{{ $det['store_name'] }}</td>
                            <td class="px-6 py-4 text-right font-bold text-gray-600" style="padding: 16px 24px; text-align: right; font-weight: 700; color: #4b5563;">{{ number_format($det['qty'], 0, ',', '.') }} pcs</td>
                            <td class="px-6 py-4 text-right font-extrabold text-gray-900" style="padding: 16px 24px; text-align: right; font-weight: 800; color: #111827;">Rp {{ number_format($det['subtotal'], 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-400" style="padding: 48px; text-align: center; color: #9ca3af;">
                                Belum ada riwayat penjualan produk ini pada rentang waktu tersebut.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="modal-footer" style="padding: 12px 24px; border-top: 1px solid #f1f5f9; background: #f8fafc; border-radius: 0 0 20px 20px; display: flex; justify-content: flex-end; flex-shrink: 0;">
                <div class="text-sm font-bold text-gray-700" style="font-size: 0.875rem; font-weight: 700; color: #374151;">
                    Total Terjual: <span class="text-blue-600 text-base font-extrabold ml-2" style="color: #2563eb; font-weight: 800; font-size: 1rem;">
                        {{ number_format(array_sum(array_column($selectedProductDetails, 'qty')), 0, ',', '.') }} pcs
                    </span>
                </div>
            </div>
        </div>
    </div>
    @endif
</x-filament-panels::page>