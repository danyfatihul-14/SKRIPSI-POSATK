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
    public int $topProductsPerPage = 10;
    public int $topProductsPage = 1;
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

    public ?string $startDate = null;
    public ?string $endDate = null;
    public ?array $selectedProductDetails = null;
    public ?string $selectedProductName = '';

    public function mount(): void
    {
        // Fetch stores
        $this->stores = \App\Models\Store::query()->pluck('name_store', 'store_id')->toArray();
        
        // Default to user's store_id if set, or null (all stores)
        $userStoreId = auth()->user()?->store_id;
        $this->storeId = $userStoreId ? (int)$userStoreId : null;

        $this->startDate = now()->startOfMonth()->toDateString();
        $this->endDate = now()->toDateString();

        $this->refreshData();
    }

    public function updatedStoreId(): void
    {
        $this->refreshData();
    }

    public function updatedStartDate(): void
    {
        $this->refreshData();
    }

    public function updatedEndDate(): void
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

        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();

        $summary = $this->getSummary($start, $end);
        $this->summaryRows[] = [
            'periode' => 'Periode Terfilter',
            'trx' => $summary['trx'],
            'uang' => $summary['uang'],
            'profit' => $summary['profit'],
        ];

        foreach ($this->getTopProducts($start, $end, 'Periode Terfilter') as $row) {
            $this->topRows[] = $row;
        }

        $this->loadTrendChartData();     
        $this->loadTopProductChartData();
        $this->loadRecentTransactions();

        $this->dispatch('sales-data-updated', [
            'labels' => $this->trendLabels,
            'sales' => $this->trendSales,
            'trx' => $this->trendTrx,
        ]);

        Log::info('Dashboard Data Loaded & Refreshed (Range)', [
            'storeId' => $this->storeId,
            'startDate' => $this->startDate,
            'endDate' => $this->endDate,
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

        if ($this->startDate) {
            $q->whereDate('t.created_at', '>=', $this->startDate);
        }
        if ($this->endDate) {
            $q->whereDate('t.created_at', '<=', $this->endDate);
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

    protected function getSummary(Carbon $start, Carbon $end): array
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

        $query = DB::table('transactions')->whereBetween('created_at', [$start, $end]);

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
                ->leftJoin('categories as c', 'c.category_id', '=', 'p.category_id')
                ->whereBetween('t.created_at', [$start, $end]);

            if ($storeId) {
                $qDetail->where('t.store_id', $storeId);
            }

            if (Schema::hasColumn('transactions', 'status')) {
                $qDetail->whereIn('t.status', ['paid', 'success', 'completed', 'selesai']);
            }

            // Fetch dynamic categories
            $dynamicCategories = DB::table('categories')
                ->where('is_dynamic_price', true)
                ->get();

            $cases = [];
            foreach ($dynamicCategories as $cat) {
                $margin = (float)($cat->profit_margin ?? 0) / 100.0;
                $conditions = ["p.category_id = {$cat->category_id}"];
                
                if (!empty($cat->keywords)) {
                    $keywordsList = json_decode($cat->keywords, true);
                    if (!is_array($keywordsList)) {
                        $keywordsList = array_map('trim', explode(',', $cat->keywords));
                    }
                    foreach ($keywordsList as $kw) {
                        if ($kw !== '') {
                            $escapedKw = str_replace("'", "''", $kw);
                            $conditions[] = "p.product_name LIKE '%{$escapedKw}%'";
                        }
                    }
                }
                
                $condStr = implode(' OR ', $conditions);
                $cases[] = "WHEN ({$condStr}) THEN ($priceExpr * {$margin}) * d.$qtyCol";
            }
            
            $casesSql = implode("\n                            ", $cases);
            if (empty($casesSql)) {
                $casesSql = "WHEN (1=0) THEN 0";
            }

            $rowDetail = $qDetail
                ->selectRaw("
                    COUNT(DISTINCT t.transaction_id) as total_trx,
                    COALESCE(SUM($priceExpr * d.$qtyCol), 0) as total_uang,
                    COALESCE(SUM(
                        CASE 
                            $casesSql
                            ELSE ($priceExpr - p.purchase_price) * d.$qtyCol
                        END
                    ), 0) as total_profit
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

    protected function getTopProducts(Carbon $start, Carbon $end, string $periode): array
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
            ->whereBetween('t.created_at', [$start, $end]);

        $storeId = $this->storeId;
        if ($storeId) {
            $query->where('t.store_id', $storeId);
        }

        if (Schema::hasColumn('transactions', 'status')) {
            $query->whereIn('t.status', ['paid', 'success', 'completed', 'selesai']);
        }

        $unitCol = $this->firstExistingColumn('products', ['unit']) ?? null;
        $unitSelect = $unitCol ? ", p.$unitCol as unit" : ", 'pcs' as unit";

        $variantCol = $this->firstExistingColumn('products', ['variant']) ?? null;
        $variantSelect = $variantCol ? ", p.$variantCol as variant" : ", '' as variant";

        $groupBy = ["p.$prodPk", "p.$prodName"];
        if ($unitCol) {
            $groupBy[] = "p.$unitCol";
        }
        if ($variantCol) {
            $groupBy[] = "p.$variantCol";
        }

        return $query
            ->groupBy($groupBy)
            ->selectRaw("
                '$periode' as periode,
                p.$prodPk as product_id,
                p.$prodName as product_name,
                SUM(d.$qtyCol) as qty,
                $omzetExpr as omzet
                $unitSelect
                $variantSelect
            ")
            ->orderByDesc('qty')
            ->get()
            ->map(fn($x) => [
                'periode' => $x->periode,
                'product_id' => $x->product_id,
                'product_name' => $x->product_name,
                'variant' => $x->variant ?? '',
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

    public function filterToday(): void
    {
        $this->startDate = now()->toDateString();
        $this->endDate = now()->toDateString();
        $this->refreshData();
    }

    public function filter3Days(): void
    {
        $this->startDate = now()->subDays(2)->toDateString();
        $this->endDate = now()->toDateString();
        $this->refreshData();
    }

    public function filter7Days(): void
    {
        $this->startDate = now()->subDays(6)->toDateString();
        $this->endDate = now()->toDateString();
        $this->refreshData();
    }

    public function filterThisMonth(): void
    {
        $this->startDate = now()->startOfMonth()->toDateString();
        $this->endDate = now()->toDateString();
        $this->refreshData();
    }

    public function filterThisYear(): void
    {
        $this->startDate = now()->startOfYear()->toDateString();
        $this->endDate = now()->toDateString();
        $this->refreshData();
    }

    public function getDateRangeLabelProperty(): string
    {
        if (! $this->startDate || ! $this->endDate) {
            return 'Periode Terfilter';
        }

        $start = Carbon::parse($this->startDate);
        $end = Carbon::parse($this->endDate);
        $diffDays = (int) $start->diffInDays($end->copy()->startOfDay());

        if ($diffDays === 0) {
            return 'Hari Ini - ' . $start->format('d M Y');
        }

        if ($start->toDateString() === now()->subDays(2)->toDateString() && $end->toDateString() === now()->toDateString()) {
            return '3 Hari Terakhir';
        }

        if ($start->toDateString() === now()->subDays(6)->toDateString() && $end->toDateString() === now()->toDateString()) {
            return '7 Hari Terakhir';
        }

        if ($start->toDateString() === now()->startOfMonth()->toDateString() && $end->toDateString() === now()->toDateString()) {
            return 'Bulan Ini (' . $start->format('M Y') . ')';
        }

        if ($start->toDateString() === now()->startOfYear()->toDateString() && $end->toDateString() === now()->toDateString()) {
            return 'Tahun Ini (' . $start->format('Y') . ')';
        }

        return $start->format('d M Y') . ' - ' . $end->format('d M Y');
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

        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();
        $diffDays = (int) $start->diffInDays($end->copy()->startOfDay());

        $q = DB::table('transactions')
            ->whereBetween('created_at', [$start, $end]);

        $storeId = $this->storeId;
        if ($storeId) {
            $q->where('store_id', $storeId);
        }

        if (Schema::hasColumn('transactions', 'status')) {
            $q->whereIn('status', ['paid', 'success', 'completed', 'selesai']);
        }

        $labels = [];
        $sales = [];
        $trx = [];

        if ($diffDays <= 0) {
            // 1 HARI (Hari Ini): Breakdown PER 1 JAM (07:00 s/d 20:00 atau jam aktif)
            $rows = $q->selectRaw("HOUR(created_at) as h, COUNT(*) as trx, COALESCE(SUM($totalColumn),0) as sales")
                ->groupBy('h')
                ->orderBy('h')
                ->get()
                ->keyBy('h');

            $startHour = 7;
            $endHour = 20; // 8 malam (20:00)

            $minH = $rows->keys()->min();
            $maxH = $rows->keys()->max();

            if ($minH !== null && (int)$minH < $startHour) {
                $startHour = (int) $minH;
            }
            if ($maxH !== null && (int)$maxH > $endHour) {
                $endHour = (int) $maxH;
            }

            for ($h = $startHour; $h <= $endHour; $h++) {
                $hourStr = sprintf('%02d:00', $h);
                $labels[] = $hourStr;
                $sales[] = (float) ($rows[$h]->sales ?? 0);
                $trx[] = (int) ($rows[$h]->trx ?? 0);
            }
        } elseif ($diffDays >= 1 && $diffDays <= 4) {
            // 2 S/D 4 HARI: Breakdown PER 4 JAM agar grafik berbentuk kurva halus (12-16 titik)
            $rows = $q->selectRaw("DATE(created_at) as d, FLOOR(HOUR(created_at) / 4) * 4 as h, COUNT(*) as trx, COALESCE(SUM($totalColumn),0) as sales")
                ->groupBy('d', 'h')
                ->orderBy('d')
                ->orderBy('h')
                ->get();

            $dataMap = [];
            foreach ($rows as $r) {
                $key = $r->d . '_' . sprintf('%02d:00', (int)$r->h);
                $dataMap[$key] = $r;
            }

            $currentDate = $start->copy()->startOfDay();
            $endDate = $end->copy()->startOfDay();

            while ($currentDate <= $endDate) {
                $dStr = $currentDate->toDateString();
                $dayFormatted = $currentDate->format('d M');

                foreach ([8, 12, 16, 20] as $h) {
                    $hStr = sprintf('%02d:00', $h);
                    $key = $dStr . '_' . $hStr;
                    $labels[] = $dayFormatted . ' ' . $hStr;
                    $sales[] = (float) ($dataMap[$key]->sales ?? 0);
                    $trx[] = (int) ($dataMap[$key]->trx ?? 0);
                }

                $currentDate->addDay();
            }
        } elseif ($diffDays > 60) {
            // LEBIH DARI 60 HARI (Misal 1 Tahun): Breakdown PER BULAN
            $rows = $q->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as m, COUNT(*) as trx, COALESCE(SUM($totalColumn),0) as sales")
                ->groupBy('m')
                ->orderBy('m')
                ->get()
                ->keyBy('m');

            $current = $start->copy()->startOfMonth();
            $endMonth = $end->copy()->startOfMonth();

            while ($current <= $endMonth) {
                $mKey = $current->format('Y-m');
                $labels[] = $current->format('M Y');
                $sales[] = (float) ($rows[$mKey]->sales ?? 0);
                $trx[] = (int) ($rows[$mKey]->trx ?? 0);
                $current->addMonth();
            }
        } else {
            // 5 HARI S/D 60 HARI (Misal 7 Hari, 14 Hari, 30 Hari): Breakdown PER TANGGAL
            $rows = $q->selectRaw("DATE(created_at) as d, COUNT(*) as trx, COALESCE(SUM($totalColumn),0) as sales")
                ->groupBy('d')
                ->orderBy('d')
                ->get()
                ->keyBy('d');

            for ($i = $diffDays; $i >= 0; $i--) {
                $date = $end->copy()->subDays($i)->toDateString();
                $labels[] = Carbon::parse($date)->format('d M');
                $sales[] = (float) ($rows[$date]->sales ?? 0);
                $trx[] = (int) ($rows[$date]->trx ?? 0);
            }
        }

        $this->trendLabels = $labels;
        $this->trendSales = $sales;
        $this->trendTrx = $trx;
    }

    protected function loadTopProductChartData(): void
    {
        $start = Carbon::parse($this->startDate)->startOfDay();
        $end = Carbon::parse($this->endDate)->endOfDay();
        $topProducts = collect($this->getTopProducts($start, $end, 'Periode Terfilter'));

        $this->topProductLabels = $topProducts->map(function ($p) {
            $name = $p['product_name'] ?? 'Produk';
            return !empty($p['variant']) ? "$name ({$p['variant']})" : $name;
        })->values()->toArray();

        $this->topProductQty = $topProducts->pluck('qty')->map(fn($x) => (int) $x)->values()->toArray();
    }

    public function showProductSalesDetail(int $productId): void
    {
        $product = \App\Models\Product::find($productId);
        if (!$product) return;

        $variantStr = !empty($product->variant) ? " ({$product->variant})" : "";
        $this->selectedProductName = $product->product_name . $variantStr;

        $detailTable = $this->firstExistingTable(['transaction_items', 'transaction_details', 'sale_items', 'sales_items']);
        $trxFk = $this->firstExistingColumn($detailTable, ['transaction_id', 'sale_id', 'sales_id']) ?? 'transaction_id';
        $qtyCol = $this->firstExistingColumn($detailTable, ['quantity', 'qty', 'jumlah']) ?? 'quantity';
        $priceCol = $this->firstExistingColumn($detailTable, ['unit_price', 'price', 'selling_price']) ?? 'unit_price';
        $subCol = $this->firstExistingColumn($detailTable, ['subtotal', 'line_total', 'total']) ?? 'subtotal';

        $query = DB::table("$detailTable as d")
            ->join('transactions as t', 't.transaction_id', '=', "d.$trxFk")
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
            d.$qtyCol as qty,
            d.$priceCol as price,
            d.$subCol as subtotal
        ")
        ->orderByDesc('t.created_at')
        ->get();

        $this->selectedProductDetails = $rows->map(fn($r) => [
            'code' => $r->code,
            'time' => \Carbon\Carbon::parse($r->time)->format('d/m/Y H:i'),
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