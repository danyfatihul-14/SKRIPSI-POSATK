{{-- filepath: resources/views/filament/pages/dashboard.blade.php --}}
<x-filament-panels::page>
    @php
    $cards = collect($summaryCards ?? [])->take(4)->values();
    $dashboardChartData = [
    'labels' => $chartData['labels'] ?? [],
    'sales' => $chartData['sales'] ?? [],
    'trx' => $chartData['trx'] ?? [],
    ];
    @endphp

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        .pd * {
            box-sizing: border-box;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .pd {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        /* ── TOP BAR ─────────────────────────────────────── */
        .pd-topbar {
            background: #fff;
            border-radius: 20px;
            padding: 16px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 16px rgba(99, 102, 241, .07);
        }

        .pd-greeting {
            font-size: 12px;
            color: #94a3b8;
            font-weight: 500;
        }

        .pd-title {
            font-size: 22px;
            font-weight: 700;
            color: #1e1b4b;
            margin-top: 2px;
        }

        .pd-topbar-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .pd-search {
            display: flex;
            align-items: center;
            gap: 8px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 9px 14px;
            width: 260px;
        }

        .pd-search svg {
            width: 15px;
            height: 15px;
            color: #94a3b8;
            flex-shrink: 0;
        }

        .pd-search input {
            border: none;
            background: transparent;
            font-size: 13px;
            color: #475569;
            outline: none;
            width: 100%;
            font-family: inherit;
        }

        .pd-search input::placeholder {
            color: #cbd5e1;
        }

        .pd-icon-btn {
            width: 38px;
            height: 38px;
            border-radius: 11px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all .15s;
        }

        .pd-icon-btn:hover {
            background: #ede9fe;
            border-color: #c4b5fd;
        }

        .pd-icon-btn svg {
            width: 16px;
            height: 16px;
            color: #64748b;
        }

        /* ── STAT CARDS ──────────────────────────────────── */
        .pd-cards {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 14px;
        }

        .pd-card {
            border-radius: 20px;
            padding: 20px;
            position: relative;
            overflow: hidden;
            transition: transform .2s, box-shadow .2s;
        }

        .pd-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, .12);
        }

        .pd-card-0 {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
        }

        .pd-card-1 {
            background: linear-gradient(135deg, #06b6d4, #0ea5e9);
        }

        .pd-card-2 {
            background: linear-gradient(135deg, #f59e0b, #f97316);
        }

        .pd-card-3 {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .pd-card-label {
            font-size: 11px;
            font-weight: 500;
            color: rgba(255, 255, 255, .75);
        }

        .pd-card-val {
            font-size: 22px;
            font-weight: 700;
            color: #fff;
            margin-top: 8px;
            line-height: 1.1;
        }

        .pd-card-change {
            display: inline-flex;
            align-items: center;
            gap: 3px;
            margin-top: 8px;
            font-size: 11px;
            font-weight: 600;
            color: #fff;
            background: rgba(255, 255, 255, .2);
            border-radius: 20px;
            padding: 2px 9px;
        }

        .pd-card-sparkline {
            position: absolute;
            bottom: 0;
            right: -4px;
            width: 90px;
            height: 48px;
            opacity: .2;
            pointer-events: none;
        }

        .pd-card-icon {
            position: absolute;
            top: 16px;
            right: 16px;
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: rgba(255, 255, 255, .2);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .pd-card-icon svg {
            width: 18px;
            height: 18px;
            color: #fff;
        }

        /* ── MIDDLE ROW ──────────────────────────────────── */
        .pd-mid {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 16px;
        }

        .pd-chart-card {
            background: #fff;
            border-radius: 20px;
            padding: 22px 24px;
            box-shadow: 0 2px 16px rgba(99, 102, 241, .07);
        }

        .pd-chart-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
        }

        .pd-chart-title {
            font-size: 15px;
            font-weight: 700;
            color: #1e1b4b;
        }

        .pd-chart-pills {
            display: flex;
            gap: 6px;
        }

        .pd-pill {
            font-size: 11px;
            font-weight: 600;
            padding: 4px 12px;
            border-radius: 20px;
            cursor: pointer;
            border: 1px solid #e2e8f0;
            color: #64748b;
            background: #f8fafc;
            transition: all .15s;
            font-family: inherit;
        }

        .pd-pill.active {
            background: #6366f1;
            color: #fff;
            border-color: #6366f1;
        }

        .pd-chart-summary {
            display: flex;
            gap: 24px;
            margin-bottom: 16px;
        }

        .pd-chart-sum-label {
            font-size: 11px;
            color: #94a3b8;
            font-weight: 500;
        }

        .pd-chart-sum-val {
            font-size: 20px;
            font-weight: 700;
            color: #1e1b4b;
            margin-top: 2px;
        }

        .pd-chart-sum-val.green {
            color: #10b981;
        }

        .pd-activity-card {
            background: #fff;
            border-radius: 20px;
            padding: 22px 20px;
            box-shadow: 0 2px 16px rgba(99, 102, 241, .07);
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .pd-activity-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 2px;
        }

        .pd-activity-title {
            font-size: 15px;
            font-weight: 700;
            color: #1e1b4b;
        }

        .pd-activity-badge {
            font-size: 11px;
            font-weight: 600;
            color: #6366f1;
            background: #ede9fe;
            border-radius: 20px;
            padding: 3px 10px;
        }

        .pd-act-item {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 10px 12px;
            border-radius: 14px;
            background: #f8fafc;
        }

        .pd-act-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            flex-shrink: 0;
            margin-top: 5px;
        }

        .pd-act-dot-green {
            background: #10b981;
        }

        .pd-act-dot-red {
            background: #ef4444;
        }

        .pd-act-title {
            font-size: 13px;
            font-weight: 600;
            color: #1e1b4b;
        }

        .pd-act-desc {
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
        }

        .pd-act-time {
            font-size: 10px;
            color: #94a3b8;
            margin-top: 3px;
        }

        /* ── BOTTOM ROW ──────────────────────────────────── */
        .pd-bottom {
            display: grid;
            grid-template-columns: 1fr 280px;
            gap: 16px;
        }

        .pd-table-card {
            background: #fff;
            border-radius: 20px;
            padding: 22px 24px;
            box-shadow: 0 2px 16px rgba(99, 102, 241, .07);
        }

        .pd-table-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 16px;
        }

        .pd-table-title {
            font-size: 15px;
            font-weight: 700;
            color: #1e1b4b;
        }

        .pd-see-all {
            font-size: 12px;
            color: #6366f1;
            font-weight: 600;
            text-decoration: none;
        }

        table.pd-table {
            width: 100%;
            border-collapse: collapse;
        }

        table.pd-table th {
            font-size: 11px;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: .06em;
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid #f1f5f9;
            font-family: inherit;
        }

        table.pd-table td {
            padding: 11px 12px;
            font-size: 13px;
            color: #475569;
            border-bottom: 1px solid #f8fafc;
        }

        table.pd-table tr:last-child td {
            border-bottom: none;
        }

        .pd-trx-code {
            font-weight: 600;
            color: #1e1b4b;
        }

        .pd-badge-paid {
            background: #dcfce7;
            color: #16a34a;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 9px;
            border-radius: 20px;
        }

        .pd-quick-card {
            background: linear-gradient(160deg, #4f46e5, #7c3aed);
            border-radius: 20px;
            padding: 22px 20px;
            color: #fff;
            box-shadow: 0 6px 24px rgba(99, 102, 241, .35);
        }

        .pd-quick-title {
            font-size: 15px;
            font-weight: 700;
            margin-bottom: 3px;
        }

        .pd-quick-sub {
            font-size: 11px;
            opacity: .65;
            margin-bottom: 20px;
        }

        .pd-quick-item {
            margin-bottom: 14px;
        }

        .pd-quick-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 5px;
        }

        .pd-quick-label {
            font-size: 12px;
            opacity: .8;
        }

        .pd-quick-val {
            font-size: 13px;
            font-weight: 700;
        }

        .pd-prog {
            height: 5px;
            background: rgba(255, 255, 255, .18);
            border-radius: 10px;
            overflow: hidden;
        }

        .pd-prog-fill {
            height: 100%;
            border-radius: 10px;
            background: rgba(255, 255, 255, .75);
        }

        .pd-quick-cta {
            display: block;
            width: 100%;
            margin-top: 20px;
            background: rgba(255, 255, 255, .15);
            border: 1px solid rgba(255, 255, 255, .25);
            color: #fff;
            font-size: 13px;
            font-weight: 600;
            padding: 10px;
            border-radius: 12px;
            text-align: center;
            text-decoration: none;
            cursor: pointer;
            transition: background .15s;
            font-family: inherit;
        }

        .pd-quick-cta:hover {
            background: rgba(255, 255, 255, .25);
        }

        @media (max-width: 1100px) {

            .pd-mid,
            .pd-bottom {
                grid-template-columns: 1fr;
            }

            .pd-cards {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 640px) {
            .pd-topbar {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .pd-search {
                width: 100%;
            }
        }
    </style>

    <div class="pd">

        {{-- TOP BAR --}}
        <div class="pd-topbar">
            <div>
                <div class="pd-greeting">Welcome back 👋</div>
                <div class="pd-title">Dashboard Overview</div>
            </div>
            <div class="pd-topbar-right">
                <div class="pd-search">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" placeholder="Search...">
                </div>
                <div class="pd-icon-btn">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- STAT CARDS --}}
        <div class="pd-cards">
            @foreach ($cards as $i => $card)
            <div class="pd-card pd-card-{{ $i }}">
                <div class="pd-card-icon">
                    @if($i===0)<svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    @elseif($i===1)<svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                    @elseif($i===2)<svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    @else<svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    @endif
                </div>
                <div class="pd-card-label">{{ $card['title'] ?? '-' }}</div>
                <div class="pd-card-val">{{ $card['value'] ?? 0 }}</div>
                @if(isset($card['change']) && $card['change'] != 0)
                <div class="pd-card-change">{{ $card['change'] > 0 ? '▲' : '▼' }} {{ abs($card['change']) }}%</div>
                @endif
                <svg class="pd-card-sparkline" viewBox="0 0 90 48" fill="none">
                    <polyline points="0,42 18,34 36,38 50,22 64,28 80,10 90,16" stroke="white" stroke-width="2.5" fill="none" />
                </svg>
            </div>
            @endforeach
        </div>

        {{-- MIDDLE: CHART + ACTIVITIES --}}
        <div class="pd-mid">

            <div class="pd-chart-card">
                <div class="pd-chart-header">
                    <div class="pd-chart-title">Growth</div>
                    <div class="pd-chart-pills">
                        <button type="button" wire:click="setRange('24H')" class="pd-pill {{ $range === '24H' ? 'active' : '' }}">24H</button>
                        <button type="button" wire:click="setRange('7D')" class="pd-pill {{ $range === '7D' ? 'active' : '' }}">7D</button>
                        <button type="button" wire:click="setRange('1M')" class="pd-pill {{ $range === '1M' ? 'active' : '' }}">1M</button>
                        <button type="button" wire:click="setRange('1Y')" class="pd-pill {{ $range === '1Y' ? 'active' : '' }}">1Y</button>
                    </div>
                </div>
                <div class="pd-chart-summary">
                    <div>
                        <div class="pd-chart-sum-label">Total Omzet (7 hari)</div>
                        <div class="pd-chart-sum-val" id="pd-total-sales">Rp —</div>
                    </div>
                    <div>
                        <div class="pd-chart-sum-label">Total Transaksi</div>
                        <div class="pd-chart-sum-val green" id="pd-total-trx">— trx</div>
                    </div>
                </div>
                <canvas id="growthChart" height="115"></canvas>
            </div>

            <div class="pd-activity-card">
                <div class="pd-activity-header">
                    <div class="pd-activity-title">Activities</div>
                    <div class="pd-activity-badge">Today</div>
                </div>
                @forelse(($activities ?? []) as $activity)
                <div class="pd-act-item">
                    <div class="pd-act-dot {{ ($activity['type'] ?? '') === 'transaction' ? 'pd-act-dot-green' : 'pd-act-dot-red' }}"></div>
                    <div>
                        <div class="pd-act-title">{{ $activity['title'] ?? '-' }}</div>
                        <div class="pd-act-desc">{{ $activity['desc'] ?? '-' }}</div>
                        <div class="pd-act-time">{{ $activity['time'] ?? '-' }}</div>
                    </div>
                </div>
                @empty
                <p style="font-size:13px;color:#94a3b8;text-align:center;padding:20px 0">Tidak ada aktivitas hari ini.</p>
                @endforelse
            </div>
        </div>

        {{-- BOTTOM: TABLE + QUICK STATS --}}
        <div class="pd-bottom">

            <div class="pd-table-card">
                <div class="pd-table-header">
                    <div class="pd-table-title">Transaksi Terbaru</div>
                    <a href="/admin/sales-reports" class="pd-see-all">Lihat semua →</a>
                </div>
                <table class="pd-table">
                    <thead>
                        <tr>
                            <th>Kode Transaksi</th>
                            <th>Waktu</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $trxActivities = array_filter($activities ?? [], fn($a) => ($a['type'] ?? '') === 'transaction'); @endphp
                        @forelse($trxActivities as $act)
                        @php $parts = explode(' - Rp ', $act['desc'] ?? ''); @endphp
                        <tr>
                            <td class="pd-trx-code">{{ $parts[0] ?? '-' }}</td>
                            <td>{{ $act['time'] ?? '-' }}</td>
                            <td style="font-weight:600;color:#10b981">Rp {{ $parts[1] ?? '-' }}</td>
                            <td><span class="pd-badge-paid">Lunas</span></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" style="text-align:center;color:#94a3b8;padding:24px 12px;font-size:13px">Belum ada transaksi hari ini.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @php
            $todayCard = collect($summaryCards ?? [])->firstWhere('title', 'Total Sales Hari Ini');
            $trxCard = collect($summaryCards ?? [])->firstWhere('title', 'Transaksi Hari Ini');
            $stockCard = collect($summaryCards ?? [])->firstWhere('title', 'Stok Menipis');
            $storeCard = collect($summaryCards ?? [])->firstWhere('title', 'Toko Aktif');
            @endphp
            <div class="pd-quick-card">
                <div class="pd-quick-title">Ringkasan Hari Ini</div>
                <div class="pd-quick-sub">Data real-time dari sistem POS</div>
                <div class="pd-quick-item">
                    <div class="pd-quick-row">
                        <span class="pd-quick-label">Omzet</span>
                        <span class="pd-quick-val">{{ $todayCard['value'] ?? 'Rp 0' }}</span>
                    </div>
                    <div class="pd-prog">
                        <div class="pd-prog-fill" style="width:65%"></div>
                    </div>
                </div>
                <div class="pd-quick-item">
                    <div class="pd-quick-row">
                        <span class="pd-quick-label">Transaksi</span>
                        <span class="pd-quick-val">{{ ($trxCard['value'] ?? 0) }} trx</span>
                    </div>
                    <div class="pd-prog">
                        <div class="pd-prog-fill" style="width:45%"></div>
                    </div>
                </div>
                <div class="pd-quick-item">
                    <div class="pd-quick-row">
                        <span class="pd-quick-label">Stok Kritis</span>
                        <span class="pd-quick-val">{{ $stockCard['value'] ?? '0 Items' }}</span>
                    </div>
                    <div class="pd-prog">
                        <div class="pd-prog-fill" style="width:20%"></div>
                    </div>
                </div>
                <div class="pd-quick-item">
                    <div class="pd-quick-row">
                        <span class="pd-quick-label">Toko Aktif</span>
                        <span class="pd-quick-val">{{ ($storeCard['value'] ?? 1) }} toko</span>
                    </div>
                    <div class="pd-prog">
                        <div class="pd-prog-fill" style="width:80%"></div>
                    </div>
                </div>
                <a href="/admin/sales-reports" class="pd-quick-cta">Lihat Laporan Lengkap</a>
            </div>

        </div>
    </div>

    @once
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @endonce

    <script>
        // benar: langsung inject object JS
        window.__dashboardData = @js($dashboardChartData);
    </script>

    <script>
        (() => {
            let growthChart = null;

            function fmt(n) {
                n = parseFloat(n) || 0;
                if (n >= 1e9) return 'Rp ' + (n / 1e9).toFixed(1) + 'M';
                if (n >= 1e6) return 'Rp ' + (n / 1e6).toFixed(1) + 'jt';
                if (n >= 1e3) return 'Rp ' + (n / 1e3).toFixed(0) + 'rb';
                return 'Rp ' + n;
            }

            function renderGrowth() {
                const el = document.getElementById('growthChart');
                if (!el || typeof Chart === 'undefined') return;

                const labels = window.__dashboardData?.labels ?? [];
                const sales = window.__dashboardData?.sales ?? [];
                const trx = window.__dashboardData?.trx ?? [];

                const lbSales = document.getElementById('pd-total-sales');
                const lbTrx = document.getElementById('pd-total-trx');
                if (lbSales) lbSales.textContent = fmt(sales.reduce((a, b) => a + Number(b || 0), 0));
                if (lbTrx) lbTrx.textContent = trx.reduce((a, b) => a + Number(b || 0), 0) + ' trx';

                if (growthChart) growthChart.destroy();

                growthChart = new Chart(el, {
                    type: 'line',
                    data: {
                        labels,
                        datasets: [{
                                label: 'Omzet',
                                data: sales,
                                borderColor: '#6366f1',
                                backgroundColor: 'rgba(99,102,241,.08)',
                                fill: true,
                                tension: .4
                            },
                            {
                                label: 'Transaksi',
                                data: trx,
                                borderColor: '#10b981',
                                backgroundColor: 'rgba(16,185,129,.07)',
                                fill: true,
                                tension: .4
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        interaction: {
                            mode: 'index',
                            intersect: false
                        }
                    }
                });
            }

            window.addEventListener('dashboard-chart-updated', (event) => {
                window.__dashboardData = event.detail?.data ?? {
                    labels: [],
                    sales: [],
                    trx: []
                };
                renderGrowth();
            });

            document.addEventListener('DOMContentLoaded', renderGrowth);
            document.addEventListener('livewire:navigated', renderGrowth);
        })();
    </script>
</x-filament-panels::page>