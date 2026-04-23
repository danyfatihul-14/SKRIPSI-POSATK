<?php
namespace App\Filament\Pages;

use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductReportDashboard extends Page
{
    protected static ?string $navigationGroup = 'Laporan';
    protected static ?string $navigationLabel = 'Laporan Produk';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationIcon = 'heroicon-o-cube';
    protected static string $view = 'filament.pages.product-report-dashboard';

    public array $topProductLabels = [];
    public array $topProductQty = [];
    public array $topRows = [];

    public function mount(): void
    {
        $this->loadTopProductChartData();
        $this->loadTopRows();
    }

    protected function loadTopProductChartData(): void
    {
        if (!Schema::hasTable('transaction_items') || !Schema::hasTable('transactions') || !Schema::hasTable('products')) {
            $this->topProductLabels = [];
            $this->topProductQty = [];
            return;
        }

        $start = Carbon::now()->startOfMonth();

        $rows = DB::table('transaction_items as d')
            ->join('transactions as t', 't.transaction_id', '=', 'd.transaction_id')
            ->join('products as p', 'p.product_id', '=', 'd.product_id')
            ->where('t.created_at', '>=', $start)
            ->whereIn('t.status', ['paid', 'success', 'completed', 'selesai'])
            ->selectRaw('p.product_name as product_name, SUM(d.quantity) as qty')
            ->groupBy('p.product_id', 'p.product_name')
            ->orderByDesc('qty')
            ->limit(10)
            ->get();

        $this->topProductLabels = $rows->pluck('product_name')->values()->all();
        $this->topProductQty = $rows->pluck('qty')->map(fn($v) => (int) $v)->values()->all();
    }

    protected function loadTopRows(): void
    {
        $periods = [
            'Harian' => Carbon::today(),
            'Mingguan' => Carbon::now()->startOfWeek(),
            'Bulanan' => Carbon::now()->startOfMonth(),
        ];

        $out = [];

        foreach ($periods as $label => $start) {
            $rows = DB::table('transaction_items as d')
                ->join('transactions as t', 't.transaction_id', '=', 'd.transaction_id')
                ->join('products as p', 'p.product_id', '=', 'd.product_id')
                ->where('t.created_at', '>=', $start)
                ->whereIn('t.status', ['paid', 'success', 'completed', 'selesai'])
                ->selectRaw('? as periode, p.product_name as product_name, SUM(d.quantity) as qty, COALESCE(SUM(d.subtotal),0) as omzet', [$label])
                ->groupBy('p.product_id', 'p.product_name')
                ->orderByDesc('qty')
                ->limit(5)
                ->get();

            foreach ($rows as $r) {
                $out[] = [
                    'periode' => (string) $r->periode,
                    'product_name' => (string) $r->product_name,
                    'qty' => (int) $r->qty,
                    'omzet' => (float) $r->omzet,
                ];
            }
        }

        $this->topRows = $out;
    }
}
