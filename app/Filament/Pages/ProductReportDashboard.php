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

    // Filters
    public ?int  $storeId       = null;
    public array $stores        = [];
    public string $periodeFilter = 'bulanan'; // harian | mingguan | bulanan | semua
    public int   $perPage       = 10;

    // Data
    public array  $topProductLabels  = [];
    public array  $topProductQty     = [];
    public array  $topRows           = [];
    public int    $totalUniqueProducts = 0;
    public int    $totalQtySold      = 0;
    public string $topProductName    = '-';
    public int    $topProductCount   = 0;

    public function mount(): void
    {
        $this->stores = \App\Models\Store::query()
            ->pluck('name_store', 'store_id')
            ->toArray();

        $userStoreId = auth()->user()?->store_id;
        $this->storeId = $userStoreId ? (int) $userStoreId : null;

        $this->refreshData();
    }

    public function updatedStoreId(): void       { $this->refreshData(); }
    public function updatedPeriodeFilter(): void  { $this->refreshData(); }
    public function updatedPerPage(): void        { $this->loadTopRows(); }

    public function refreshData(): void
    {
        $this->loadTopProductChartData();
        $this->loadTopRows();
    }

    // ── helpers ────────────────────────────────────────────────────────────────

    private function periodStart(): Carbon
    {
        return match ($this->periodeFilter) {
            'harian'   => Carbon::today(),
            'mingguan' => Carbon::now()->startOfWeek(),
            default    => Carbon::now()->startOfMonth(), // bulanan + semua
        };
    }

    private function applyBaseFilters($query): mixed
    {
        if ($this->periodeFilter !== 'semua') {
            $query->where('t.created_at', '>=', $this->periodStart());
        }

        if ($this->storeId) {
            $query->where('t.store_id', $this->storeId);
        }

        $query->whereIn('t.status', ['paid', 'success', 'completed', 'selesai']);

        return $query;
    }

    // ── chart data ─────────────────────────────────────────────────────────────

    protected function loadTopProductChartData(): void
    {
        if (
            !Schema::hasTable('transaction_items') ||
            !Schema::hasTable('transactions') ||
            !Schema::hasTable('products')
        ) {
            $this->topProductLabels = [];
            $this->topProductQty    = [];
            return;
        }

        $query = DB::table('transaction_items as d')
            ->join('transactions as t', 't.transaction_id', '=', 'd.transaction_id')
            ->join('products as p', 'p.product_id', '=', 'd.product_id');

        $this->applyBaseFilters($query);

        $results = $query
            ->selectRaw('p.product_name as product_name, SUM(d.quantity) as qty')
            ->groupBy('p.product_id', 'p.product_name')
            ->orderByDesc('qty')
            ->limit(10)
            ->get();

        $this->topProductLabels    = $results->pluck('product_name')->values()->all();
        $this->topProductQty       = $results->pluck('qty')->map(fn($v) => (int) $v)->values()->all();
        $this->totalUniqueProducts = count($this->topProductLabels);
        $this->totalQtySold        = array_sum($this->topProductQty);
        $this->topProductName      = $this->topProductLabels[0] ?? '-';
        $this->topProductCount     = $this->topProductQty[0] ?? 0;
    }

    // ── table rows ─────────────────────────────────────────────────────────────

    protected function loadTopRows(): void
    {
        if (
            !Schema::hasTable('transaction_items') ||
            !Schema::hasTable('transactions') ||
            !Schema::hasTable('products')
        ) {
            $this->topRows = [];
            return;
        }

        $hasUnit = Schema::hasColumn('products', 'unit');
        $unitSelect = $hasUnit ? ', p.unit as unit' : ", 'pcs' as unit";

        if ($this->periodeFilter === 'semua') {
            // Single combined query — no grouping by period label
            $query = DB::table('transaction_items as d')
                ->join('transactions as t', 't.transaction_id', '=', 'd.transaction_id')
                ->join('products as p', 'p.product_id', '=', 'd.product_id');

            $this->applyBaseFilters($query);

            $rows = $query
                ->selectRaw("'Semua' as periode, p.product_name as product_name, SUM(d.quantity) as qty, COALESCE(SUM(d.subtotal),0) as omzet $unitSelect")
                ->groupBy($hasUnit ? ['p.product_id', 'p.product_name', 'p.unit'] : ['p.product_id', 'p.product_name'])
                ->orderByDesc('qty')
                ->limit($this->perPage)
                ->get();

            $this->topRows = $rows->map(fn($r) => [
                'periode'      => 'Semua',
                'product_name' => (string) $r->product_name,
                'qty'          => (int)    $r->qty,
                'omzet'        => (float)  $r->omzet,
                'unit'         => $r->unit ?? 'pcs',
            ])->toArray();

            return;
        }

        // Single period selected
        $label = match ($this->periodeFilter) {
            'harian'   => 'Harian',
            'mingguan' => 'Mingguan',
            default    => 'Bulanan',
        };

        $query = DB::table('transaction_items as d')
            ->join('transactions as t', 't.transaction_id', '=', 'd.transaction_id')
            ->join('products as p', 'p.product_id', '=', 'd.product_id');

        $this->applyBaseFilters($query);

        $rows = $query
            ->selectRaw("? as periode, p.product_name as product_name, SUM(d.quantity) as qty, COALESCE(SUM(d.subtotal),0) as omzet $unitSelect", [$label])
            ->groupBy($hasUnit ? ['p.product_id', 'p.product_name', 'p.unit'] : ['p.product_id', 'p.product_name'])
            ->orderByDesc('qty')
            ->limit($this->perPage)
            ->get();

        $this->topRows = $rows->map(fn($r) => [
            'periode'      => (string) $r->periode,
            'product_name' => (string) $r->product_name,
            'qty'          => (int)    $r->qty,
            'omzet'        => (float)  $r->omzet,
            'unit'         => $r->unit ?? 'pcs',
        ])->toArray();
    }
}
