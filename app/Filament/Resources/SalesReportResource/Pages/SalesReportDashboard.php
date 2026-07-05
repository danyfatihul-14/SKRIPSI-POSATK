<?php
// filepath: e:\Polinema\Semester8\Skripsi\pos\app\Filament\Resources\SalesReportResource\Pages\SalesReportDashboard.php

namespace App\Filament\Resources\SalesReportResource\Pages;

use App\Filament\Resources\SalesReportResource;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class SalesReportDashboard extends Page
{
    protected static string $resource = SalesReportResource::class;
    protected static string $view = 'filament.resources.sales-report-resource.pages.sales-report-dashboard';

    public ?int $storeId = null;
    public array $stores = [];
    public int $perPage = 10;
    public array $summaryRows = [];
    public array $topRows = [];
    public array $recentTransactions = [];
    public ?array $selectedTransaction = null;
    public array $selectedTransactionItems = [];
    public array $trendLabels = [];
    public array $trendSales = [];
    public array $trendTrx = [];
    public array $topProductLabels = [];
    public array $topProductQty = [];

    public function mount(): void
    {
        // Fetch stores
        $this->stores = \App\Models\Store::query()->pluck('name_store', 'store_id')->toArray();
        
        // Default to user's store_id if set, or null (all stores)
        $userStoreId = auth()->user()?->store_id;
        if ($userStoreId) {
            $this->storeId = (int)$userStoreId;
        } else {
            $this->storeId = null;
        }

        $this->refreshData();
    }

    public function updatedStoreId(): void
    {
        $this->refreshData();
    }

    public function updatedPerPage(): void
    {
        $this->loadRecentTransactions();
    }

    public function refreshData(): void
    {
        $this->summaryRows = [];
        $this->topRows = [];
        $this->recentTransactions = [];
        $this->selectedTransaction = null;
        $this->selectedTransactionItems = [];
        $this->trendLabels = [];
        $this->trendSales = [];
        $this->trendTrx = [];
        $this->topProductLabels = [];
        $this->topProductQty = [];

        $periods = [
            'Harian' => now()->startOfDay(),
            'Mingguan' => now()->startOfWeek(),
            'Bulanan' => now()->startOfMonth(),
        ];

        foreach ($periods as $label => $start) {
            $summary = $this->getSummary($start);
            $this->summaryRows[] = [
                'periode' => $label,
                'trx' => $summary['trx'],
                'uang' => $summary['uang'],
                'profit' => $summary['profit'],
            ];

            foreach ($this->getTopProducts($start, $label) as $row) {
                $this->topRows[] = $row;
            }
        }

        $this->loadTrendChartData();     
        $this->loadTopProductChartData();
        $this->loadRecentTransactions();

        $this->dispatch('sales-data-updated', [
            'labels' => $this->trendLabels,
            'sales' => $this->trendSales,
            'trx' => $this->trendTrx,
        ]);

        Log::info('Dashboard Data Loaded & Refreshed', [
            'storeId' => $this->storeId,
            'trendLabels' => count($this->trendLabels ?? []),
            'trendSales' => count($this->trendSales ?? []),
            'trendTrx' => count($this->trendTrx ?? []),
            'topProductLabels' => count($this->topProductLabels ?? []),
            'topProductQty' => count($this->topProductQty ?? []),
        ]);
    }

    public function showTransactionDetail(string $transactionId): void
    {
        $trx = collect($this->recentTransactions)->firstWhere('id', $transactionId);

        if (!$trx) {
            return;
        }

        $this->selectedTransaction = $trx;
        $this->selectedTransactionItems = $this->getTransactionItems($transactionId);
    }

    protected function loadRecentTransactions(): void
    {
        if (!Schema::hasTable('transactions')) {
            $this->recentTransactions = [];
            return;
        }

        $trxIdCol = $this->firstExistingColumn('transactions', ['transaction_id', 'id']) ?? 'transaction_id';
        $codeCol = $this->firstExistingColumn('transactions', ['transaction_code', 'code']) ?? $trxIdCol;
        $totalCol = $this->firstExistingColumn('transactions', ['total_amount', 'grand_total', 'total', 'final_total', 'amount']) ?? 'total_amount';
        $methodCol = $this->firstExistingColumn('transactions', ['payment_method', 'payment_type', 'method']) ?? 'payment_method';

        $q = DB::table('transactions as t');

        $storeId = $this->storeId;
        if ($storeId) {
            $q->where('t.store_id', $storeId);
        }

        if (Schema::hasTable('users') && Schema::hasColumn('transactions', 'user_id') && Schema::hasColumn('users', 'user_id')) {
            $q->leftJoin('users as u', 'u.user_id', '=', 't.user_id');

            if (Schema::hasColumn('users', 'name')) {
                $q->selectRaw('u.name');
            } elseif (Schema::hasColumn('users', 'username')) {
                $q->selectRaw('u.username');
            }
        }

        $q->selectRaw("t.$trxIdCol as id")
            ->selectRaw("t.$codeCol as code")
            ->selectRaw("t.created_at as created_at")
            ->selectRaw("t.$totalCol as total")
            ->selectRaw("t.$methodCol as method");

        if (Schema::hasColumn('transactions', 'status')) {
            $q->whereIn('t.status', ['completed', 'paid', 'success', 'selesai']);
        }

        $rows = $q->orderByDesc('t.created_at')->limit($this->perPage)->get();

        $this->recentTransactions = $rows->map(function ($x) {
            $cashier = '-';

            // Cek name atau username dari join
            if (!empty($x->name)) {
                $cashier = $x->name;
            } elseif (!empty($x->username)) {
                $cashier = $x->username;
            }

            return [
                'id' => (string) $x->id,
                'code' => (string) $x->code,
                'time' => Carbon::parse($x->created_at)->format('d/m/Y H:i:s'),
                'cashier' => $cashier,
                'method' => strtoupper((string) ($x->method ?? '-')),
                'total' => (float) ($x->total ?? 0),
            ];
        })->toArray();
    }

    protected function getTransactionItems(string $transactionId): array
    {
        $detailTable = $this->firstExistingTable(['transaction_items', 'transaction_details', 'sale_items', 'sales_items']);
        if (!$detailTable || !Schema::hasTable('products')) return [];

        $trxFk = $this->firstExistingColumn($detailTable, ['transaction_id', 'sale_id', 'sales_id']) ?? 'transaction_id';
        $prodFk = $this->firstExistingColumn($detailTable, ['product_id', 'item_id']) ?? 'product_id';
        $qtyCol = $this->firstExistingColumn($detailTable, ['quantity', 'qty', 'jumlah']) ?? 'quantity';
        $priceCol = $this->firstExistingColumn($detailTable, ['unit_price', 'price', 'selling_price']);
        $subtotalCol = $this->firstExistingColumn($detailTable, ['subtotal', 'line_total', 'total']);

        $prodPk = $this->firstExistingColumn('products', ['product_id', 'id']) ?? 'product_id';
        $prodName = $this->firstExistingColumn('products', ['product_name', 'name']) ?? 'product_name';

        $selectHarga = $priceCol ? "d.$priceCol as price" : "0 as price";
        $selectSubtotal = $subtotalCol
            ? "d.$subtotalCol as subtotal"
            : ($priceCol ? "(d.$qtyCol * d.$priceCol) as subtotal" : "0 as subtotal");

        $unitSelect = Schema::hasColumn('products', 'unit') ? ", p.unit as unit" : ", 'pcs' as unit";

        return DB::table("$detailTable as d")
            ->join('products as p', "p.$prodPk", '=', "d.$prodFk")
            ->where("d.$trxFk", $transactionId)
            ->selectRaw("p.$prodName as product_name, d.$qtyCol as qty, $selectHarga, $selectSubtotal $unitSelect")
            ->get()
            ->map(fn($x) => [
                'product_name' => $x->product_name,
                'qty' => (int) $x->qty,
                'price' => (float) $x->price,
                'subtotal' => (float) $x->subtotal,
                'unit' => $x->unit ?? 'pcs',
            ])->toArray();
    }

    protected function getSummary(Carbon $start): array
    {
        if (!Schema::hasTable('transactions')) {
            return ['trx' => 0, 'uang' => 0, 'profit' => 0];
        }

        $totalColumn = $this->firstExistingColumn('transactions', [
            'total_amount',
            'grand_total',
            'total',
            'final_total',
            'amount'
        ]) ?? 'total_amount';

        $query = DB::table('transactions')->where('created_at', '>=', $start);

        $storeId = $this->storeId;
        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        if (Schema::hasColumn('transactions', 'status')) {
            $query->whereIn('status', ['paid', 'success', 'completed', 'selesai']);
        }

        $row = $query
            ->selectRaw("COUNT(*) as total_trx, COALESCE(SUM($totalColumn),0) as total_uang")
            ->first();

        // Safe fallback profit to 20% of sales
        $profit = (float) ($row->total_uang ?? 0) * 0.20;

        // Try exact calculation if tables exist
        $detailTable = $this->firstExistingTable(['transaction_items', 'transaction_details', 'sale_items', 'sales_items']);
        if ($detailTable && Schema::hasTable('products')) {
            $trxFk = $this->firstExistingColumn($detailTable, ['transaction_id', 'sale_id', 'sales_id']) ?? 'transaction_id';
            $prodFk = $this->firstExistingColumn($detailTable, ['product_id', 'item_id']) ?? 'product_id';
            $qtyCol = $this->firstExistingColumn($detailTable, ['quantity', 'qty', 'jumlah']) ?? 'quantity';
            $priceCol = $this->firstExistingColumn($detailTable, ['unit_price', 'price', 'selling_price']);
            $prodPk = $this->firstExistingColumn('products', ['product_id', 'id']) ?? 'product_id';
            $priceExpr = $priceCol ? "d.$priceCol" : "0";

            $qDetail = DB::table("$detailTable as d")
                ->join('transactions as t', "t.transaction_id", '=', "d.$trxFk")
                ->join('products as p', "p.$prodPk", '=', "d.$prodFk")
                ->where('t.created_at', '>=', $start);

            if ($storeId) {
                $qDetail->where('t.store_id', $storeId);
            }

            if (Schema::hasColumn('transactions', 'status')) {
                $qDetail->whereIn('t.status', ['paid', 'success', 'completed', 'selesai']);
            }

            $rowDetail = $qDetail
                ->selectRaw("
                    COUNT(DISTINCT t.transaction_id) as total_trx,
                    COALESCE(SUM($priceExpr * d.$qtyCol), 0) as total_uang,
                    COALESCE(SUM(($priceExpr - p.purchase_price) * d.$qtyCol), 0) as total_profit
                ")
                ->first();

            return [
                'trx' => (int) ($rowDetail->total_trx ?? 0),
                'uang' => (float) ($rowDetail->total_uang ?? 0),
                'profit' => (float) ($rowDetail->total_profit ?? 0),
            ];
        }

        return [
            'trx' => (int) ($row->total_trx ?? 0),
            'uang' => (float) ($row->total_uang ?? 0),
            'profit' => $profit,
        ];
    }

    protected function getTopProducts(Carbon $start, string $periode): array
    {
        $detailTable = $this->firstExistingTable(['transaction_items', 'transaction_details', 'sale_items', 'sales_items']);
        if (!$detailTable || !Schema::hasTable('transactions') || !Schema::hasTable('products')) {
            return [];
        }

        $trxPk = $this->firstExistingColumn('transactions', ['transaction_id', 'id']) ?? 'transaction_id';
        $trxFk = $this->firstExistingColumn($detailTable, ['transaction_id', 'sale_id', 'sales_id']) ?? 'transaction_id';

        $prodPk = $this->firstExistingColumn('products', ['product_id', 'id']) ?? 'product_id';
        $prodFk = $this->firstExistingColumn($detailTable, ['product_id', 'item_id']) ?? 'product_id';
        $prodName = $this->firstExistingColumn('products', ['product_name', 'name']) ?? 'product_name';

        $qtyCol = $this->firstExistingColumn($detailTable, ['quantity', 'qty', 'jumlah']) ?? 'quantity';
        $subtotalCol = $this->firstExistingColumn($detailTable, ['subtotal', 'line_total', 'total']);
        $priceCol = $this->firstExistingColumn($detailTable, ['unit_price', 'price', 'selling_price']);

        $omzetExpr = $subtotalCol
            ? "COALESCE(SUM(d.$subtotalCol),0)"
            : ($priceCol ? "COALESCE(SUM(d.$qtyCol * d.$priceCol),0)" : "0");

        $query = DB::table("$detailTable as d")
            ->join('transactions as t', "t.$trxPk", '=', "d.$trxFk")
            ->join('products as p', "p.$prodPk", '=', "d.$prodFk")
            ->where('t.created_at', '>=', $start);

        $storeId = $this->storeId;
        if ($storeId) {
            $query->where('t.store_id', $storeId);
        }

        if (Schema::hasColumn('transactions', 'status')) {
            $query->whereIn('t.status', ['paid', 'success', 'completed', 'selesai']);
        }

        $unitCol = $this->firstExistingColumn('products', ['unit']) ?? null;
        $unitSelect = $unitCol ? ", p.$unitCol as unit" : ", 'pcs' as unit";
        $unitGroupBy = $unitCol ? ", p.$unitCol" : "";

        $groupBy = ["p.$prodPk", "p.$prodName"];
        if ($unitCol) {
            $groupBy[] = "p.$unitCol";
        }

        return $query
            ->groupBy($groupBy)
            ->selectRaw("
                '$periode' as periode,
                p.$prodName as product_name,
                SUM(d.$qtyCol) as qty,
                $omzetExpr as omzet
                $unitSelect
            ")
            ->orderByDesc('qty')
            ->limit(5)
            ->get()
            ->map(fn($x) => [
                'periode' => $x->periode,
                'product_name' => $x->product_name,
                'qty' => (int) $x->qty,
                'omzet' => (float) $x->omzet,
                'unit' => $x->unit ?? 'pcs',
            ])
            ->toArray();
    }

    protected function firstExistingTable(array $tables): ?string
    {
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) return $table;
        }
        return null;
    }

    protected function firstExistingColumn(string $table, array $columns): ?string
    {
        foreach ($columns as $col) {
            if (Schema::hasColumn($table, $col)) return $col;
        }
        return null;
    }

    protected function loadTrendChartData(): void
    {
        if (!Schema::hasTable('transactions')) return;

        $totalColumn = $this->firstExistingColumn('transactions', [
            'total_amount',
            'grand_total',
            'total',
            'final_total',
            'amount'
        ]) ?? 'total_amount';

        $start = now()->subDays(29)->startOfDay();

        $q = DB::table('transactions')
            ->where('created_at', '>=', $start);

        $storeId = $this->storeId;
        if ($storeId) {
            $q->where('store_id', $storeId);
        }

        if (Schema::hasColumn('transactions', 'status')) {
            $q->whereIn('status', ['paid', 'success', 'completed', 'selesai']);
        }

        $rows = $q->selectRaw("DATE(created_at) as d, COUNT(*) as trx, COALESCE(SUM($totalColumn),0) as sales")
            ->groupBy('d')
            ->orderBy('d')
            ->get()
            ->keyBy('d');

        $labels = [];
        $sales = [];
        $trx = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $labels[] = \Carbon\Carbon::parse($date)->format('d M');
            $sales[] = (float) ($rows[$date]->sales ?? 0);
            $trx[] = (int) ($rows[$date]->trx ?? 0);
        }

        $this->trendLabels = $labels;
        $this->trendSales = $sales;
        $this->trendTrx = $trx;
    }

    protected function loadTopProductChartData(): void
    {
        $monthlyTop = collect($this->getTopProducts(now()->startOfMonth(), 'Bulanan'));

        $this->topProductLabels = $monthlyTop->pluck('product_name')->values()->toArray();
        $this->topProductQty = $monthlyTop->pluck('qty')->map(fn($x) => (int) $x)->values()->toArray();
    }
}
