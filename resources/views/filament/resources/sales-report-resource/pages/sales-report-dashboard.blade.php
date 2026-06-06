<x-filament-panels::page>
    @php
        $harian = collect($summaryRows)->firstWhere('periode', 'Harian') ?? ['trx' => 0, 'uang' => 0, 'profit' => 0];
        $mingguan = collect($summaryRows)->firstWhere('periode', 'Mingguan') ?? ['trx' => 0, 'uang' => 0, 'profit' => 0];
        $bulanan = collect($summaryRows)->firstWhere('periode', 'Bulanan') ?? ['trx' => 0, 'uang' => 0, 'profit' => 0];
        
        $avgTicket = $bulanan['trx'] > 0 ? ($bulanan['uang'] / $bulanan['trx']) : 0;
        $profitMargin = $bulanan['uang'] > 0 ? (($bulanan['profit'] / $bulanan['uang']) * 100) : 0;

        // Extract Top 5 Products for the Month
        $topProductsMonth = collect($topRows)->where('periode', 'Bulanan')->sortByDesc('qty')->take(5);
        $maxQty = $topProductsMonth->max('qty') ?: 1;
    @endphp

    <style>
        /* Modern Premium Palette & Styles */
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

        /* Executive Stat Cards */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        @media (min-width: 640px) {
            .stat-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
        @media (min-width: 1024px) {
            .stat-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        }

        .stat-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02), 0 2px 8px rgba(0,0,0,0.04);
            position: relative;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.02);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
        }

        .stat-card-primary::before { background: #3b82f6; }
        .stat-card-success::before { background: #10b981; }
        .stat-card-warning::before { background: #f59e0b; }
        .stat-card-purple::before { background: #8b5cf6; }

        .stat-icon-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            border-radius: 10px;
            margin-bottom: 12px;
        }

        .stat-icon-primary { background: #eff6ff; color: #3b82f6; }
        .stat-icon-success { background: #ecfdf5; color: #10b981; }
        .stat-icon-warning { background: #fffbeb; color: #f59e0b; }
        .stat-icon-purple { background: #f5f3ff; color: #8b5cf6; }

        .stat-label {
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .stat-value {
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
            margin-top: 4px;
            line-height: 1;
        }

        .stat-subtitle {
            font-size: 11px;
            color: #94a3b8;
            margin-top: 6px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .badge-green {
            background: #d1fae5;
            color: #065f46;
            padding: 2px 6px;
            border-radius: 9999px;
            font-weight: 700;
            font-size: 10px;
        }

        /* Tables & Grid Layout */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 20px;
            margin-bottom: 24px;
        }
        @media (min-width: 1024px) {
            .dashboard-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }

        .card-panel {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02), 0 2px 8px rgba(0,0,0,0.04);
            overflow: hidden;
        }

        .card-panel-header {
            padding: 16px 20px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-panel-title {
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .progress-bar-container {
            width: 100%;
            background-color: #f1f5f9;
            border-radius: 9999px;
            height: 6px;
            overflow: hidden;
            margin-top: 4px;
        }

        .progress-bar-fill {
            height: 100%;
            border-radius: 9999px;
            background: linear-gradient(90deg, #3b82f6, #60a5fa);
        }

        /* Buttons & Badges */
        .btn-print {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #0f172a;
            color: white;
            padding: 8px 16px;
            border-radius: 10px;
            font-size: 13px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
            box-shadow: 0 4px 6px -1px rgba(15, 23, 42, 0.15);
        }

        .btn-print:hover {
            background: #1e293b;
            transform: translateY(-1px);
        }

        .btn-print:active {
            transform: translateY(0);
        }

        .badge-method {
            display: inline-flex;
            align-items: center;
            font-weight: 700;
            font-size: 10px;
            padding: 3px 8px;
            border-radius: 6px;
            text-transform: uppercase;
        }

        .badge-method-cash {
            background: #fffbeb;
            color: #b45309;
            border: 1px solid #fde68a;
        }

        .badge-method-qris {
            background: #ecfdf5;
            color: #047857;
            border: 1px solid #a7f3d0;
        }

        /* Modal Overlay */
        .modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.55);
            backdrop-filter: blur(4px);
            z-index: 9998;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            animation: fadeIn 0.15s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to   { opacity: 1; }
        }

        .modal-box {
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 60px -10px rgba(0,0,0,0.25), 0 10px 25px -5px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 680px;
            max-height: 85vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            animation: slideUp 0.2s ease;
        }

        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to   { transform: translateY(0);    opacity: 1; }
        }

        .modal-header {
            padding: 20px 24px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            background: #f8fafc;
            border-radius: 20px 20px 0 0;
            flex-shrink: 0;
        }

        .modal-body {
            overflow-y: auto;
            flex: 1;
        }

        .modal-footer {
            padding: 12px 24px;
            border-top: 1px solid #f1f5f9;
            background: #f8fafc;
            border-radius: 0 0 20px 20px;
            display: flex;
            justify-content: flex-end;
            flex-shrink: 0;
        }

        .btn-close-modal {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #fee2e2;
            color: #dc2626;
            border: none;
            padding: 7px 14px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.15s;
        }

        .btn-close-modal:hover { background: #fecaca; }

        /* Sticky header for scrollable tables */
        .sticky-header th {
            position: sticky;
            top: 0;
            background: #f8fafc !important; /* solid background so rows don't show through */
            z-index: 10;
            box-shadow: inset 0 -1px 0 #e2e8f0;
        }

        /* PRINT STYLES */
        @media print {
            body {
                background: white !important;
                color: black !important;
            }
            .fi-sidebar, .fi-topbar, .no-print, .btn-print, .fi-breadcrumbs {
                display: none !important;
            }
            .fi-main {
                padding: 0 !important;
                margin: 0 !important;
            }
            .stat-card {
                border: 1px solid #cbd5e1 !important;
                box-shadow: none !important;
                background: white !important;
            }
            .card-panel {
                border: 1px solid #cbd5e1 !important;
                box-shadow: none !important;
                background: white !important;
                page-break-inside: avoid;
            }
            #printReportArea {
                display: block !important;
                width: 100% !important;
            }
        }
    </style>

    <div class="px-4 py-4 md:px-6 md:py-6 lg:px-8 lg:py-8">
        <div id="printReportArea" class="space-y-6">
            {{-- Print-only Header --}}
            <div class="hidden print:block border-b-2 border-slate-300 pb-4 mb-6">
                <div class="flex justify-between items-end">
                    <div>
                        <h2 class="text-2xl font-bold text-slate-800">Laporan Analisis & Kinerja Penjualan</h2>
                        <p class="text-sm text-slate-500 mt-1">Dokumen formal performa toko, omzet kotor, dan laba bersih owner.</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-semibold text-slate-700">
                            Toko: {{ $storeId ? ($stores[$storeId] ?? 'Toko Tidak Dikenal') : 'Semua Toko' }}
                        </p>
                        <p class="text-xs text-slate-400 mt-1">Dicetak pada: {{ now()->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
            </div>

            {{-- Report Executive Header --}}
            <div class="report-header no-print">
                <div class="report-header-title">
                    <h2>Laporan Analisis & Kinerja Penjualan</h2>
                    <p>Dokumen formal performa toko, omzet kotor, dan laba bersih owner.</p>
                </div>
                <div class="flex items-center gap-3">
                    {{-- Store Filter --}}
                    <div class="w-64">
                        <x-filament::input.wrapper>
                            <x-filament::input.select wire:model.live="storeId" style="font-weight: 600; color: #1e293b;">
                                <option value="">✨ Semua Toko</option>
                                @foreach($stores as $id => $name)
                                    <option value="{{ $id }}">🏪 {{ $name }}</option>
                                @endforeach
                            </x-filament::input.select>
                        </x-filament::input.wrapper>
                    </div>

                    <button type="button" onclick="window.print()" class="btn-print">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:16px; height:16px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.89l-1.92-1.92L2.88 13.89M17.28 13.89l1.92-1.92 1.92 1.92M9 19.5h6a2.25 2.25 0 002.25-2.25V15M9 19.5a2.25 2.25 0 01-2.25-2.25V15m12 4.5a2.25 2.25 0 002.25-2.25V15m-15 0h15M9 4.5h6a2.25 2.25 0 012.25 2.25V9M9 4.5A2.25 2.25 0 006.75 6.75V9m10.5-2.25A2.25 2.25 0 0119.5 9m0 0v6M4.5 9v6M9 9h6" />
                        </svg>
                        Cetak Laporan (PDF)
                    </button>
                </div>
            </div>

        {{-- Executive Metrics --}}
            <div class="stat-grid">
                {{-- Omzet Kotor --}}
            <div class="stat-card stat-card-primary">
                <div class="stat-icon-wrapper stat-icon-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width:20px; height:20px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5h16.5M4.5 19.25h15M5.25 4.5V17.25M18.75 4.5V17.25M9.75 9.75h4.5m-4.5 3h4.5" />
                    </svg>
                </div>
                <div class="stat-label">Omzet Kotor (Bulan Ini)</div>
                <div class="stat-value">Rp {{ number_format($bulanan['uang'], 0, ',', '.') }}</div>
                <div class="stat-subtitle">
                    Total penerimaan kotor toko
                </div>
            </div>

            {{-- Pendapatan Owner (Keuntungan Bersih) --}}
            <div class="stat-card stat-card-success">
                <div class="stat-icon-wrapper stat-icon-success">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width:20px; height:20px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879-.659c1.546-1.16 3.696-1.16 5.242 0l.879.659M9.75 9.75c1.546-1.16 3.696-1.16 5.242 0L15.75 10.5" />
                    </svg>
                </div>
                <div class="stat-label">Pendapatan Owner (Bersih)</div>
                <div class="stat-value text-emerald-600">Rp {{ number_format($bulanan['profit'], 0, ',', '.') }}</div>
                <div class="stat-subtitle">
                    Laba bersih (Omzet - Modal)
                </div>
            </div>

            {{-- Total Transaksi --}}
            <div class="stat-card stat-card-warning">
                <div class="stat-icon-wrapper stat-icon-warning">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width:20px; height:20px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.03 0 1.9.693 2.166 1.638m-7.377 12.408l-2.28 2.28m0 0l-2.28-2.28m2.28 2.28V10.5" />
                    </svg>
                </div>
                <div class="stat-label">Transaksi Selesai</div>
                <div class="stat-value">{{ number_format($bulanan['trx'], 0, ',', '.') }} Trx</div>
                <div class="stat-subtitle">
                    Volume transaksi sukses
                </div>
            </div>

            {{-- Rata-Rata Transaksi & Margin --}}
            <div class="stat-card stat-card-purple">
                <div class="stat-icon-wrapper stat-icon-purple">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width:20px; height:20px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6a7.5 7.5 0 107.5 7.5h-7.5V6z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5H21A7.5 7.5 0 0013.5 3v7.5z" />
                    </svg>
                </div>
                <div class="stat-label">Margin Keuntungan Bersih</div>
                <div class="stat-value">{{ number_format($profitMargin, 1, ',', '.') }}%</div>
                <div class="stat-subtitle">
                    Rata-rata: Rp {{ number_format($avgTicket, 0, ',', '.') }} / trx
                </div>
            </div>
        </div>

        {{-- First Grid Row: Trend Charts --}}
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
            <div class="card-panel" wire:ignore>
                <div class="card-panel-header">
                    <div class="card-panel-title">
                        <span style="color:#3b82f6;">📊</span> Trend Grafik Omzet (30 Hari Terakhir)
                    </div>
                </div>
                <div class="p-6">
                    <canvas id="omzetTrendChart" height="130"></canvas>
                </div>
            </div>

            <div class="card-panel" wire:ignore>
                <div class="card-panel-header">
                    <div class="card-panel-title">
                        <span style="color:#f59e0b;">📈</span> Trend Grafik Transaksi (30 Hari Terakhir)
                    </div>
                </div>
                <div class="p-6">
                    <canvas id="trxTrendChart" height="130"></canvas>
                </div>
            </div>
        </div>

        {{-- Second Grid Row: Tables --}}
        <div class="dashboard-grid">
            {{-- Data Penjualan Ringkasan --}}
            <div class="card-panel">
                <div class="card-panel-header">
                    <div class="card-panel-title">
                        <span>📋</span> Ringkasan Laporan Berkala
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full table-auto text-sm">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="px-6 py-3.5 text-left font-bold text-gray-600 text-xs uppercase tracking-wide">Periode</th>
                                <th class="px-6 py-3.5 text-right font-bold text-gray-600 text-xs uppercase tracking-wide">Transaksi</th>
                                <th class="px-6 py-3.5 text-right font-bold text-gray-600 text-xs uppercase tracking-wide">Omzet Kotor</th>
                                <th class="px-6 py-3.5 text-right font-bold text-emerald-700 text-xs uppercase tracking-wide">Laba Bersih</th>
                                <th class="px-6 py-3.5 text-right font-bold text-gray-600 text-xs uppercase tracking-wide">Margin (%)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($summaryRows as $row)
                                @php
                                    $rowMargin = $row['uang'] > 0 ? (($row['profit'] / $row['uang']) * 100) : 0;
                                @endphp
                                <tr class="hover:bg-blue-50/40 transition-colors">
                                    <td class="px-6 py-4 font-bold text-gray-800">{{ $row['periode'] }}</td>
                                    <td class="px-6 py-4 text-right font-semibold text-gray-600">{{ number_format($row['trx'], 0, ',', '.') }} Trx</td>
                                    <td class="px-6 py-4 text-right font-semibold text-gray-800">Rp {{ number_format($row['uang'], 0, ',', '.') }}</td>
                                    <td class="px-6 py-4 text-right font-extrabold text-emerald-600">Rp {{ number_format($row['profit'], 0, ',', '.') }}</td>
                                    <td class="px-6 py-4 text-right font-bold">
                                        <span class="badge-green">{{ number_format($rowMargin, 1, ',', '.') }}%</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-10 text-center text-gray-400">Belum ada data</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Top 5 Produk Terlaris --}}
            <div class="card-panel">
                <div class="card-panel-header">
                    <div class="card-panel-title">
                        <span>🏆</span> Top 5 Produk Terlaris (Bulan Ini)
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full table-auto text-sm">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="px-6 py-3.5 text-center font-bold text-gray-600 text-xs uppercase tracking-wide w-12">No</th>
                                <th class="px-6 py-3.5 text-left font-bold text-gray-600 text-xs uppercase tracking-wide">Nama Produk</th>
                                <th class="px-6 py-3.5 text-right font-bold text-gray-600 text-xs uppercase tracking-wide w-28">Jml Terjual</th>
                                <th class="px-6 py-3.5 text-left font-bold text-gray-600 text-xs uppercase tracking-wide w-36">Visualitas</th>
                                <th class="px-6 py-3.5 text-right font-bold text-gray-600 text-xs uppercase tracking-wide">Omzet</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($topProductsMonth as $index => $row)
                                @php
                                    $percentage = ($row['qty'] / $maxQty) * 100;
                                @endphp
                                <tr class="hover:bg-blue-50/40 transition-colors">
                                    <td class="px-6 py-4 text-center font-extrabold text-gray-300 text-base">{{ $loop->iteration }}</td>
                                    <td class="px-6 py-4 font-bold text-gray-800 text-sm">{{ $row['product_name'] }}</td>
                                    <td class="px-6 py-4 text-right font-bold text-gray-700 text-sm">{{ number_format($row['qty'], 0, ',', '.') }} {{ strtoupper($row['unit'] ?? 'pcs') }}</td>
                                    <td class="px-6 py-4">
                                        <div class="progress-bar-container">
                                            <div class="progress-bar-fill" style="width: {{ $percentage }}%;"></div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right font-extrabold text-blue-600 text-sm">Rp {{ number_format($row['omzet'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-10 text-center text-gray-400">Tidak ada produk terlaris di periode ini</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

            {{-- Third Row: Recent Transactions --}}
            <div class="card-panel">
                <div class="card-panel-header">
                    <div class="card-panel-title">
                        <span>🧾</span> Log Transaksi Penjualan Terbaru
                    </div>
                    {{-- Per-page selector toolbar --}}
                    <div class="flex items-center gap-3 no-print">
                        <span class="text-xs text-gray-500 font-medium">Tampilkan:</span>
                        <div class="flex items-center gap-1">
                            @foreach([10, 20, 50, 100] as $opt)
                                <button
                                    wire:click="$set('perPage', {{ $opt }})"
                                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition-all
                                           {{ $perPage == $opt
                                              ? 'bg-blue-600 text-white shadow-sm'
                                              : 'bg-gray-100 text-gray-600 hover:bg-gray-200' }}"
                                >
                                    {{ $opt }}
                                </button>
                            @endforeach
                        </div>
                        <span class="text-xs text-gray-400">baris</span>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full table-auto text-sm">
                        <thead class="bg-gray-50 border-b sticky-header">
                            <tr>
                                <th class="px-6 py-3.5 text-left font-bold text-gray-600 text-xs uppercase tracking-wide">Kode Struk</th>
                                <th class="px-6 py-3.5 text-left font-bold text-gray-600 text-xs uppercase tracking-wide">Tanggal & Waktu</th>
                                <th class="px-6 py-3.5 text-left font-bold text-gray-600 text-xs uppercase tracking-wide">Melayani (Kasir)</th>
                                <th class="px-6 py-3.5 text-center font-bold text-gray-600 text-xs uppercase tracking-wide w-36">Metode Pembayaran</th>
                                <th class="px-6 py-3.5 text-right font-bold text-gray-600 text-xs uppercase tracking-wide">Total Belanja</th>
                                <th class="px-6 py-3.5 text-center font-bold text-gray-600 text-xs uppercase tracking-wide w-28 no-print">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($recentTransactions as $trx)
                            <tr class="hover:bg-blue-50/40 transition-colors">
                                <td class="px-6 py-4 font-bold text-blue-600 text-sm">{{ $trx['code'] }}</td>
                                <td class="px-6 py-4 text-gray-500 text-sm">{{ $trx['time'] }}</td>
                                <td class="px-6 py-4 font-semibold text-gray-700 text-sm">{{ $trx['cashier'] }}</td>
                                <td class="px-6 py-4 text-center">
                                    <span class="badge-method {{ $trx['method'] === 'QRIS' ? 'badge-method-qris' : 'badge-method-cash' }}">
                                        {{ $trx['method'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right font-extrabold text-gray-800 text-sm">Rp {{ number_format($trx['total'], 0, ',', '.') }}</td>
                                <td class="px-6 py-4 text-center no-print">
                                    <button
                                        wire:click="showTransactionDetail('{{ $trx['id'] }}')"
                                        class="px-4 py-1.5 rounded-lg bg-gray-100 hover:bg-blue-100 hover:text-blue-700 text-gray-600 text-xs font-bold transition-all border border-gray-200 hover:border-blue-200">
                                        Detail Struk
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-400">
                                    <div class="flex flex-col items-center gap-2">
                                        <span class="text-3xl">📭</span>
                                        <span class="font-medium">Belum ada transaksi terekam</span>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{-- Footer info --}}
                <div class="px-6 py-3 border-t border-gray-100 bg-gray-50/50 flex items-center justify-between no-print">
                    <span class="text-xs text-gray-400">
                        Menampilkan <strong class="text-gray-600">{{ count($recentTransactions) }}</strong> transaksi terbaru
                    </span>
                    <span class="text-xs text-gray-400">Batas: {{ $perPage }} baris</span>
                </div>
            </div>

        </div>
    </div>

    {{-- Transaction Detail Modal --}}
    @if($selectedTransaction)
    <div class="modal-backdrop no-print" wire:click.self="$set('selectedTransaction', null)">
        <div class="modal-box">
            {{-- Modal Header --}}
            <div class="modal-header">
                <div>
                    <div class="text-xs font-semibold text-blue-500 uppercase tracking-widest mb-1">🧾 Detail Transaksi</div>
                    <div class="font-extrabold text-lg text-gray-900">{{ $selectedTransaction['code'] }}</div>
                    <div class="text-xs text-gray-400 mt-1 flex items-center gap-3">
                        <span>👤 {{ $selectedTransaction['cashier'] }}</span>
                        <span>🕐 {{ $selectedTransaction['time'] }}</span>
                        <span class="badge-method {{ $selectedTransaction['method'] === 'QRIS' ? 'badge-method-qris' : 'badge-method-cash' }}">
                            {{ $selectedTransaction['method'] }}
                        </span>
                    </div>
                </div>
                <button
                    wire:click="$set('selectedTransaction', null)"
                    class="btn-close-modal ml-4 flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" style="width:14px;height:14px;"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    Tutup
                </button>
            </div>

            {{-- Modal Body: Items Table --}}
            <div class="modal-body">
                <table class="w-full table-auto text-sm">
                    <thead class="bg-gray-50 border-b sticky-header">
                        <tr>
                            <th class="px-6 py-3.5 text-left font-bold text-gray-600 text-xs uppercase tracking-wide">Nama Produk</th>
                            <th class="px-6 py-3.5 text-right font-bold text-gray-600 text-xs uppercase tracking-wide w-24">Jumlah</th>
                            <th class="px-6 py-3.5 text-right font-bold text-gray-600 text-xs uppercase tracking-wide">Harga Satuan</th>
                            <th class="px-6 py-3.5 text-right font-bold text-gray-600 text-xs uppercase tracking-wide">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($selectedTransactionItems as $item)
                        <tr class="hover:bg-blue-50/30 transition-colors">
                            <td class="px-6 py-4 font-semibold text-gray-800">{{ $item['product_name'] }}</td>
                            <td class="px-6 py-4 text-right font-bold text-gray-600">{{ number_format($item['qty'], 0, ',', '.') }} {{ strtoupper($item['unit'] ?? 'pcs') }}</td>
                            <td class="px-6 py-4 text-right text-gray-500">Rp {{ number_format($item['price'], 0, ',', '.') }}</td>
                            <td class="px-6 py-4 text-right font-extrabold text-gray-900">Rp {{ number_format($item['subtotal'], 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center text-gray-400">
                                <div class="flex flex-col items-center gap-2">
                                    <span class="text-3xl">📦</span>
                                    <span class="font-medium">Rincian item tidak ditemukan.</span>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Modal Footer: Total --}}
            <div class="modal-footer">
                <div class="text-sm font-bold text-gray-700">
                    Total Belanja: <span class="text-blue-600 text-base font-extrabold ml-2">Rp {{ number_format($selectedTransaction['total'], 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>
    @endif

    @once
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @endonce

    @php
    $chartData = [
        'labels' => $trendLabels ?? [],
        'sales' => $trendSales ?? [],
        'trx' => $trendTrx ?? [],
    ];
    @endphp

    <script>
        window.__salesChartData = @json($chartData);
    </script>

    <script>
        (() => {
            let omzetChart = null;
            let trxChart = null;

            function getChartData() {
                return window.__salesChartData ?? {
                    labels: [],
                    sales: [],
                    trx: []
                };
            }

            function renderCharts() {
                const omzetEl = document.getElementById('omzetTrendChart');
                const trxEl = document.getElementById('trxTrendChart');
                if (!omzetEl || !trxEl || typeof Chart === 'undefined') return;

                const {
                    labels,
                    sales,
                    trx
                } = getChartData();

                if (omzetChart) omzetChart.destroy();
                if (trxChart) trxChart.destroy();

                omzetChart = new Chart(omzetEl, {
                    type: 'line',
                    data: {
                        labels,
                        datasets: [{
                            label: 'Omzet Penjualan (Rp)',
                            data: sales,
                            borderColor: '#3b82f6',
                            backgroundColor: 'rgba(59, 130, 246, 0.08)',
                            fill: true,
                            tension: 0.38,
                            borderWidth: 2.5,
                            pointBackgroundColor: '#2563eb',
                            pointHoverRadius: 6
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                grid: {
                                    color: '#f1f5f9'
                                },
                                ticks: {
                                    callback: function(value) {
                                        return 'Rp ' + value.toLocaleString('id-ID');
                                    }
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                });

                trxChart = new Chart(trxEl, {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [{
                            label: 'Volume Transaksi',
                            data: trx,
                            backgroundColor: '#f59e0b',
                            borderRadius: 6,
                            barPercentage: 0.65
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                grid: {
                                    color: '#f1f5f9'
                                },
                                ticks: {
                                    stepSize: 1
                                }
                            },
                            x: {
                                grid: {
                                    display: false
                                }
                            }
                        }
                    }
                });
            }

            document.addEventListener('DOMContentLoaded', renderCharts);
            document.addEventListener('livewire:navigated', renderCharts);
            document.addEventListener('sales-data-updated', (event) => {
                if (event.detail) {
                    const data = event.detail.data || event.detail[0] || event.detail;
                    if (data && data.labels) {
                        window.__salesChartData = data;
                    }
                }
                renderCharts();
            });
        })();
    </script>
</x-filament-panels::page>