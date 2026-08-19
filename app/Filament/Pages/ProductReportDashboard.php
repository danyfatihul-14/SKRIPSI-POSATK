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
    public ?string $startDate   = null;
    public ?string $endDate     = null;
    public int   $perPage       = 10;
    public int   $page          = 1;

    // Data
    public array  $topProductLabels  = [];
    public array  $topProductQty     = [];
    public array  $topRows           = [];
    public int    $totalUniqueProducts = 0;
    public int    $totalQtySold      = 0;
    public string $topProductName    = '-';
    public int    $topProductCount   = 0;
    public ?array $selectedProductDetails = null;
    public ?string $selectedProductName = '';

    public function mount(): void
    {
        $this->stores = \App\Models\Store::query()
            ->pluck('name_store', 'store_id')
            ->toArray();

        $userStoreId = auth()->user()?->store_id;
        $this->storeId = $userStoreId ? (int) $userStoreId : null;

        $this->startDate = now()->startOfMonth()->toDateString();
        $this->endDate = now()->toDateString();

        $this->refreshData();
    }

    public function updatedStoreId(): void       { $this->refreshData(); }
    public function updatedStartDate(): void     { $this->refreshData(); }
    public function updatedEndDate(): void       { $this->refreshData(); }
    public function updatedPerPage(): void        { $this->page = 1; $this->loadTopRows(); }

    public function refreshData(): void
    {
        $this->page = 1;
        $this->loadTopProductChartData();
        $this->loadTopRows();
        $this->dispatch('product-chart-updated', labels: $this->topProductLabels, qty: $this->topProductQty);
    }

    // ── helpers ────────────────────────────────────────────────────────────────

    private function applyBaseFilters($query): mixed
    {
        if ($this->startDate) {
            $query->whereDate('t.created_at', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->whereDate('t.created_at', '<=', $this->endDate);
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

        $hasVariant = Schema::hasColumn('products', 'variant');
        $variantSelect = $hasVariant ? ', p.variant as variant' : ", '' as variant";

        $results = $query
            ->selectRaw("p.product_name as product_name, SUM(d.quantity) as qty $variantSelect")
            ->groupBy($hasVariant ? ['p.product_id', 'p.product_name', 'p.variant'] : ['p.product_id', 'p.product_name'])
            ->orderByDesc('qty')
            ->get();

        $this->topProductLabels = $results->map(function ($r) {
            $v = trim($r->variant ?? '');
            return ($v !== '' && $v !== '-') ? "{$r->product_name} ($v)" : $r->product_name;
        })->take(10)->values()->all();

        $this->topProductQty       = $results->take(10)->pluck('qty')->map(fn($v) => (int) $v)->values()->all();
        $this->totalUniqueProducts = count($results);
        $this->totalQtySold        = (int) $results->sum('qty');
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
        
        $hasVariant = Schema::hasColumn('products', 'variant');
        $variantSelect = $hasVariant ? ', p.variant as variant' : ", '' as variant";
        
        $prodPk = Schema::hasColumn('products', 'product_id') ? 'product_id' : 'id';

        $query = DB::table('transaction_items as d')
            ->join('transactions as t', 't.transaction_id', '=', 'd.transaction_id')
            ->join('products as p', 'p.product_id', '=', 'd.product_id');

        $this->applyBaseFilters($query);

        $groupBy = ["p.$prodPk", 'p.product_name'];
        if ($hasUnit) $groupBy[] = 'p.unit';
        if ($hasVariant) $groupBy[] = 'p.variant';

        $rows = $query
            ->selectRaw("p.$prodPk as product_id, p.product_name as product_name, SUM(d.quantity) as qty, COALESCE(SUM(d.subtotal),0) as omzet $unitSelect $variantSelect")
            ->groupBy($groupBy)
            ->orderByDesc('qty')
            ->get();

        $this->topRows = $rows->map(fn($r) => [
            'periode'      => 'Periode Terfilter',
            'product_id'   => (int)    $r->product_id,
            'product_name' => (string) $r->product_name,
            'variant'      => (string) ($r->variant ?? ''),
            'qty'          => (int)    $r->qty,
            'omzet'        => (float)  $r->omzet,
            'unit'         => $r->unit ?? 'pcs',
        ])->toArray();
    }

    public function showProductSalesDetail(int $productId): void
    {
        $product = \App\Models\Product::find($productId);
        if (!$product) return;

        $vStr = !empty($product->variant) && $product->variant !== '-' ? " ({$product->variant})" : '';
        $this->selectedProductName = $product->product_name . $vStr;

        $query = DB::table('transaction_items as d')
            ->join('transactions as t', 't.transaction_id', '=', 'd.transaction_id')
            ->join('stores as s', 's.store_id', '=', 't.store_id')
            ->where('d.product_id', $productId);

        if ($this->storeId) {
            $query->where('t.store_id', $this->storeId);
        }

        if ($this->startDate) {
            $query->whereDate('t.created_at', '>=', $this->startDate);
        }
        if ($this->endDate) {
            $query->whereDate('t.created_at', '<=', $this->endDate);
        }

        $query->whereIn('t.status', ['paid', 'success', 'completed', 'selesai']);

        $rows = $query->selectRaw("
            t.transaction_code as code,
            t.created_at as time,
            s.name_store as store_name,
            d.quantity as qty,
            d.unit_price as price,
            d.subtotal as subtotal
        ")
        ->orderByDesc('t.created_at')
        ->get();

        $this->selectedProductDetails = $rows->map(fn($r) => [
            'code' => $r->code,
            'time' => Carbon::parse($r->time)->format('d/m/Y H:i'),
            'store_name' => $r->store_name,
            'qty' => (int) $r->qty,
            'price' => (float) $r->price,
            'subtotal' => (float) $r->subtotal,
        ])->toArray();
    }

    public function closeProductSalesDetail(): void
    {
        $this->selectedProductDetails = null;
        $this->selectedProductName = '';
    }
}
