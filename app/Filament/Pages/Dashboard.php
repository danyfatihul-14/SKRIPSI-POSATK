<?php

namespace App\Filament\Pages;

use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Dashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static string $view = 'filament.pages.dashboard';

    public array $summaryCards = [];
    public array $activities = [];
    public string $range = '7D';
    public array $chartData = [
        'labels' => [],
        'sales' => [],
        'trx' => [],
    ];

    public function mount(): void
    {
        $this->loadSummaryCards();
        $this->loadChartData($this->range);
        $this->loadActivities();
    }

    public function setRange(string $range): void
    {
        if (!in_array($range, ['24H', '7D', '1M', '1Y'], true)) {
            return;
        }

        $this->range = $range;
        $this->loadChartData($range);

        $this->dispatch('dashboard-chart-updated', data: $this->chartData);
    }

    protected function loadSummaryCards(): void
    {
        if (!Schema::hasTable('transactions')) {
            $this->summaryCards = [];
            return;
        }

        $today = Carbon::today();
        $lastWeek = Carbon::today()->subDays(7);

        $todayData = DB::table('transactions')
            ->where('created_at', '>=', $today)
            ->whereIn('status', ['paid', 'success', 'completed', 'selesai'])
            ->selectRaw('COUNT(*) as trx_count, COALESCE(SUM(total_amount),0) as total_sales')
            ->first();

        $weekData = DB::table('transactions')
            ->whereBetween('created_at', [$lastWeek, Carbon::yesterday()->endOfDay()])
            ->whereIn('status', ['paid', 'success', 'completed', 'selesai'])
            ->selectRaw('COALESCE(SUM(total_amount),0) as total_sales')
            ->first();

        $todaySales = (float) ($todayData->total_sales ?? 0);
        $weekSales = (float) ($weekData->total_sales ?? 0);
        $salesChange = $weekSales > 0 ? (($todaySales - $weekSales) / $weekSales * 100) : 0;

        $lowStock = 0;
        $activeStores = 1;

        if (Schema::hasTable('stock_level')) {
            $lowStock = DB::table('stock_level')
                ->select('product_id')
                ->groupBy('product_id')
                ->havingRaw('SUM(quantity) < 10')
                ->get()
                ->count();

            $activeStores = DB::table('stock_level')
                ->distinct()
                ->count('store_id');
        }

        $this->summaryCards = [
            [
                'title' => 'Total Sales Hari Ini',
                'value' => 'Rp ' . number_format($todaySales, 0, ',', '.'),
                'change' => round($salesChange, 1),
                'icon' => 'heroicon-o-banknotes',
                'color' => 'emerald',
            ],
            [
                'title' => 'Transaksi Hari Ini',
                'value' => (int) ($todayData->trx_count ?? 0),
                'change' => 0,
                'icon' => 'heroicon-o-arrow-trending-up',
                'color' => 'blue',
            ],
            [
                'title' => 'Stok Menipis',
                'value' => $lowStock . ' Items',
                'change' => 0,
                'icon' => 'heroicon-o-exclamation-triangle',
                'color' => 'rose',
            ],
            [
                'title' => 'Toko Aktif',
                'value' => $activeStores,
                'change' => 0,
                'icon' => 'heroicon-o-building-storefront',
                'color' => 'amber',
            ],
        ];
    }

    protected function loadChartData(?string $range = null): void
    {
        $range = $range ?? $this->range;

        if (!Schema::hasTable('transactions')) {
            $this->chartData = ['labels' => [], 'sales' => [], 'trx' => []];
            return;
        }

        $status = ['paid', 'success', 'completed', 'selesai'];

        if ($range === '24H') {
            $rows = DB::table('transactions')
                ->selectRaw('HOUR(created_at) as h, COUNT(*) as trx, COALESCE(SUM(total_amount),0) as sales')
                ->where('created_at', '>=', now()->startOfDay())
                ->whereIn('status', $status)
                ->groupBy('h')
                ->orderBy('h')
                ->get();

            $map = $rows->keyBy('h');
            $labels = [];
            $sales = [];
            $trx = [];

            for ($i = 0; $i < 24; $i++) {
                $labels[] = str_pad((string) $i, 2, '0', STR_PAD_LEFT) . ':00';
                $sales[] = (float) (($map[$i]->sales ?? 0));
                $trx[] = (int) (($map[$i]->trx ?? 0));
            }
        } elseif ($range === '1Y') {
            $rows = DB::table('transactions')
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as p, COUNT(*) as trx, COALESCE(SUM(total_amount),0) as sales")
                ->where('created_at', '>=', now()->subMonths(11)->startOfMonth())
                ->whereIn('status', $status)
                ->groupBy('p')
                ->orderBy('p')
                ->get()
                ->keyBy('p');

            $labels = [];
            $sales = [];
            $trx = [];

            for ($i = 11; $i >= 0; $i--) {
                $d = now()->subMonths($i);
                $key = $d->format('Y-m');
                $labels[] = $d->format('M y');
                $sales[] = (float) (($rows[$key]->sales ?? 0));
                $trx[] = (int) (($rows[$key]->trx ?? 0));
            }
        } else {
            $days = $range === '1M' ? 30 : 7;

            $rows = DB::table('transactions')
                ->selectRaw('DATE(created_at) as d, COUNT(*) as trx, COALESCE(SUM(total_amount),0) as sales')
                ->where('created_at', '>=', now()->subDays($days - 1)->startOfDay())
                ->whereIn('status', $status)
                ->groupBy('d')
                ->orderBy('d')
                ->get()
                ->keyBy('d');

            $labels = [];
            $sales = [];
            $trx = [];

            for ($i = $days - 1; $i >= 0; $i--) {
                $d = now()->subDays($i);
                $key = $d->toDateString();
                $labels[] = $d->format('d M');
                $sales[] = (float) (($rows[$key]->sales ?? 0));
                $trx[] = (int) (($rows[$key]->trx ?? 0));
            }
        }

        $this->chartData = compact('labels', 'sales', 'trx');
    }

    protected function loadActivities(): void
    {
        $activities = [];

        if (Schema::hasTable('transactions')) {
            $recentTrx = DB::table('transactions')
                ->where('created_at', '>=', Carbon::today())
                ->whereIn('status', ['paid', 'success', 'completed', 'selesai'])
                ->orderByDesc('created_at')
                ->limit(3)
                ->get();

            foreach ($recentTrx as $trx) {
                $activities[] = [
                    'type' => 'transaction',
                    'title' => 'Transaksi Berhasil',
                    'desc' => ($trx->transaction_code ?? '-') . ' - Rp ' . number_format((float) ($trx->total_amount ?? 0), 0, ',', '.'),
                    'time' => Carbon::parse($trx->created_at)->diffForHumans(),
                    'icon' => 'heroicon-o-check-circle',
                ];
            }
        }

        // FIX: ambil stok kritis dari stock_level, bukan products.stock
        if (Schema::hasTable('stock_level') && Schema::hasTable('products')) {
            $lowStocks = DB::table('stock_level as s')
                ->join('products as p', 'p.product_id', '=', 's.product_id')
                ->selectRaw('p.product_name, SUM(s.quantity) as qty_total')
                ->groupBy('p.product_id', 'p.product_name')
                ->havingRaw('SUM(s.quantity) < 5')
                ->orderBy('qty_total')
                ->limit(2)
                ->get();

            foreach ($lowStocks as $row) {
                $activities[] = [
                    'type' => 'alert',
                    'title' => 'Stok Kritis',
                    'desc' => $row->product_name . ' - Sisa: ' . (int) $row->qty_total . ' unit',
                    'time' => 'Sekarang',
                    'icon' => 'heroicon-o-exclamation-triangle',
                ];
            }
        }

        $this->activities = array_slice($activities, 0, 5);
    }
}
