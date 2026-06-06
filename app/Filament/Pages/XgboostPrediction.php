<?php

namespace App\Filament\Pages;

use App\Models\MlPrediction;
use App\Models\Product;
use App\Models\Store;
use App\Models\TransactionItem;
use App\Services\MlService;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class XgboostPrediction extends Page
{
    protected static ?string $navigationGroup = 'Machine Learning';
    protected static ?string $navigationLabel = 'XGBoost Prediksi';
    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.pages.xgboost-prediction';

    public ?int $storeId = null;
    public int $days = 7;

    public array $stores = [];
    public array $history = [];
    public array $dailyRecommendations = [];
    public string $runStatusMessage = '';
    public array $runSummary = [
        'products' => 0,
        'recommended_items' => 0,
        'raw_predicted_units' => 0,
        'recommended_units' => 0,
    ];

    public function mount(): void
    {
        $this->stores = Store::query()
            ->orderBy('name_store')
            ->get(['store_id', 'name_store'])
            ->map(fn (Store $store): array => [
                'id' => (int) $store->store_id,
                'name' => (string) $store->name_store,
            ])
            ->all();

        $this->storeId = $this->storeId ?? Auth::user()?->store_id ?? ($this->stores[0]['id'] ?? null);

        $this->loadHistory();
        $this->loadLatestRecommendations();
    }

    public function runPrediction(): void
    {
        if (! $this->storeId) {
            Notification::make()->danger()->title('Pilih toko terlebih dahulu')->send();
            return;
        }
        $store = Store::query()->find($this->storeId);
        if (! $store) {
            Notification::make()->danger()->title('Store tidak valid')->send();
            return;
        }

        $targetProducts = Product::query()->with('category')->get();

        $success = 0;
        $failed = 0;
        $dailyRecommendations = [];
        $rawPredictedUnits = 0;
        $recommendedUnits = 0;
        $recommendedItems = 0;

        foreach ($targetProducts as $product) {
            try {
                $lag1 = (int) TransactionItem::query()
                    ->where('product_id', $product->product_id)
                    ->whereHas('transaction', function ($q) use ($store) {
                        $q->where('store_id', $store->store_id)->whereDate('created_at', now()->subDay());
                    })->sum('quantity');

                $lag7 = (int) TransactionItem::query()
                    ->where('product_id', $product->product_id)
                    ->whereHas('transaction', function ($q) use ($store) {
                        $q->where('store_id', $store->store_id)->whereDate('created_at', '>=', now()->subDays(7));
                    })->sum('quantity');

                $mlResult = app(MlService::class)->predict(
                    $product->product_name,
                    $product->category?->category_name ?? 'Uncategorized',
                    $product->unit ?? 'pcs',
                    $lag1,
                    $lag7,
                    $this->days
                );

                $prediksiHarian = $mlResult['prediksi_harian'] ?? [];
                $rawPredictedUnits += (int) ($mlResult['total_prediksi'] ?? 0);
                $startDate = $prediksiHarian[0]['tanggal'] ?? null;
                $endDate = $prediksiHarian[count($prediksiHarian) - 1]['tanggal'] ?? null;

                // Anti-overstock: cap prediksi harian berdasarkan histori, lalu hitung kebutuhan kulak
                // dengan mempertimbangkan stok awal toko.
                $avg7 = (int) ceil($lag7 / 7);
                $maxDailyCap = max(1, $lag1 * 2, $avg7 * 2);
                $remainingStock = max(0, $product->stockInStore($store->store_id));
                $dailyRecommendationsForProduct = [];
                $totalRecommendedQty = 0;

                foreach ($prediksiHarian as $prediksi) {
                    $tanggal = $prediksi['tanggal'] ?? null;
                    $rawQty = (int) ($prediksi['prediksi'] ?? 0);

                    if (! $tanggal || $rawQty <= 0) {
                        continue;
                    }

                    $cappedQty = min($rawQty, $maxDailyCap);
                    $recommendQty = max($cappedQty - $remainingStock, 0);
                    $remainingStock = max($remainingStock - $cappedQty, 0);

                    $dailyRecommendationsForProduct[] = [
                        'tanggal' => $tanggal,
                        'raw_prediksi' => $rawQty,
                        'capped_prediksi' => $cappedQty,
                        'rekomendasi_kulak' => $recommendQty,
                    ];

                    if ($recommendQty <= 0) {
                        continue;
                    }

                    if (! isset($dailyRecommendations[$tanggal])) {
                        $dailyRecommendations[$tanggal] = [
                            'date' => $tanggal,
                            'total_qty' => 0,
                            'total_cost' => 0,
                            'items' => [],
                        ];
                    }

                    $itemCost = $recommendQty * (int) ($product->selling_price ?? 0);
                    $dailyRecommendations[$tanggal]['total_qty'] += $recommendQty;
                    $dailyRecommendations[$tanggal]['total_cost'] += $itemCost;
                    $dailyRecommendations[$tanggal]['items'][] = [
                        'product_name' => (string) $product->product_name,
                        'qty' => $recommendQty,
                        'unit' => (string) ($product->unit ?? 'pcs'),
                        'estimated_cost' => $itemCost,
                    ];

                    $totalRecommendedQty += $recommendQty;
                    $recommendedUnits += $recommendQty;
                    $recommendedItems++;
                }

                if ($totalRecommendedQty <= 0) {
                    $success++;
                    continue;
                }

                MlPrediction::create([
                    'store_id' => $store->store_id,
                    'product_id' => $product->product_id,
                    'prediction_date' => now()->toDateString(),
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'selling_price' => $product->selling_price ?? 0,
                    'predicted_revenue' => $totalRecommendedQty * ($product->selling_price ?? 0),
                    'predicted_quantity' => (int) $totalRecommendedQty,
                    'confidence_level' => 0.85,
                    'featured_used' => [
                        'lag_1' => $lag1,
                        'lag_7' => $lag7,
                        'days' => $this->days,
                        'stock_start' => (int) $product->stockInStore($store->store_id),
                        'max_daily_cap' => $maxDailyCap,
                        'daily_predictions' => $prediksiHarian,
                        'daily_recommendations' => $dailyRecommendationsForProduct,
                    ],
                    'model_version' => '1.0',
                ]);

                $success++;
            } catch (\Throwable $e) {
                $failed++;
                Log::warning('Bulk prediction failed for product ' . ($product->product_id ?? 'unknown') . ': ' . $e->getMessage());
            }
        }

        ksort($dailyRecommendations);
        foreach ($dailyRecommendations as &$day) {
            usort($day['items'], static fn (array $a, array $b): int => $b['qty'] <=> $a['qty']);
        }
        unset($day);

        $this->dailyRecommendations = array_values($dailyRecommendations);
        $this->runSummary = [
            'products' => $success,
            'recommended_items' => $recommendedItems,
            'raw_predicted_units' => $rawPredictedUnits,
            'recommended_units' => $recommendedUnits,
        ];

        if ($recommendedUnits <= 0) {
            $this->runStatusMessage = 'Prediksi ML sudah selesai, tetapi setelah dicek stok dan batas aman, rekomendasi kulak final menjadi 0. Ini biasanya terjadi kalau stok masih cukup atau histori penjualan sangat rendah/nihil.';
        } else {
            $this->runStatusMessage = sprintf('Rekomendasi kulak final tersedia untuk %d item dengan total %d unit.', $recommendedItems, $recommendedUnits);
        }

        Notification::make()
            ->success()
            ->title('Prediksi selesai')
            ->body(sprintf('Berhasil: %d produk, Gagal: %d produk', $success, $failed))
            ->send();

        $this->loadHistory();
    }

    public function loadHistory(): void
    {
        $grouped = MlPrediction::query()
            ->with('store')
            ->latest('created_at')
            ->limit(200)
            ->get()
            ->groupBy(function (MlPrediction $prediction): string {
                return implode('|', [
                    $prediction->store_id,
                    optional($prediction->prediction_date)->format('Y-m-d') ?? '-',
                    optional($prediction->start_date)->format('Y-m-d') ?? '-',
                    optional($prediction->end_date)->format('Y-m-d') ?? '-',
                ]);
            })
            ->take(12);

        $this->history = $grouped
            ->map(function ($group): array {
                /** @var MlPrediction $first */
                $first = $group->first();

                return [
                    'date' => optional($first->prediction_date)->format('Y-m-d') ?? '-',
                    'store' => $first->store?->name_store ?? '-',
                    'product_count' => $group->count(),
                    'predicted_quantity' => (int) $group->sum('predicted_quantity'),
                    'revenue' => (float) $group->sum('predicted_revenue'),
                    'start_date' => optional($first->start_date)->format('Y-m-d') ?? null,
                    'end_date' => optional($first->end_date)->format('Y-m-d') ?? null,
                    'confidence' => (float) $group->avg('confidence_level'),
                ];
            })
            ->values()
            ->all();
    }

    public function loadLatestRecommendations(): void
    {
        if (! $this->storeId) {
            $this->dailyRecommendations = [];
            return;
        }

        $latest = MlPrediction::query()
            ->where('store_id', $this->storeId)
            ->latest('created_at')
            ->first();

        if (! $latest) {
            $this->dailyRecommendations = [];
            return;
        }

        $rows = MlPrediction::query()
            ->with('product')
            ->where('store_id', $this->storeId)
            ->whereDate('prediction_date', $latest->prediction_date);

        if ($latest->start_date) {
            $rows->whereDate('start_date', $latest->start_date);
        } else {
            $rows->whereNull('start_date');
        }

        if ($latest->end_date) {
            $rows->whereDate('end_date', $latest->end_date);
        } else {
            $rows->whereNull('end_date');
        }

        $rows = $rows->get();

        $dailyRecommendations = [];

        foreach ($rows as $row) {
            $dailyRecommended = $row->featured_used['daily_recommendations'] ?? [];

            if (! empty($dailyRecommended) && is_array($dailyRecommended)) {
                foreach ($dailyRecommended as $prediksi) {
                    $tanggal = $prediksi['tanggal'] ?? null;
                    $qty = (int) ($prediksi['rekomendasi_kulak'] ?? 0);

                    if (! $tanggal || $qty <= 0) {
                        continue;
                    }

                    if (! isset($dailyRecommendations[$tanggal])) {
                        $dailyRecommendations[$tanggal] = [
                            'date' => $tanggal,
                            'total_qty' => 0,
                            'total_cost' => 0,
                            'items' => [],
                        ];
                    }

                    $estimatedCost = $qty * (int) ($row->selling_price ?? 0);
                    $dailyRecommendations[$tanggal]['total_qty'] += $qty;
                    $dailyRecommendations[$tanggal]['total_cost'] += $estimatedCost;
                    $dailyRecommendations[$tanggal]['items'][] = [
                        'product_name' => $row->product?->product_name ?? '-',
                        'qty' => $qty,
                        'unit' => $row->product?->unit ?? 'pcs',
                        'estimated_cost' => $estimatedCost,
                    ];
                }

                continue;
            }

            $daily = $row->featured_used['daily_predictions'] ?? [];
            foreach ($daily as $prediksi) {
                $tanggal = $prediksi['tanggal'] ?? null;
                $qty = (int) ($prediksi['prediksi'] ?? 0);

                if (! $tanggal || $qty <= 0) {
                    continue;
                }

                if (! isset($dailyRecommendations[$tanggal])) {
                    $dailyRecommendations[$tanggal] = [
                        'date' => $tanggal,
                        'total_qty' => 0,
                        'total_cost' => 0,
                        'items' => [],
                    ];
                }

                $estimatedCost = $qty * (int) ($row->selling_price ?? 0);
                $dailyRecommendations[$tanggal]['total_qty'] += $qty;
                $dailyRecommendations[$tanggal]['total_cost'] += $estimatedCost;
                $dailyRecommendations[$tanggal]['items'][] = [
                    'product_name' => $row->product?->product_name ?? '-',
                    'qty' => $qty,
                    'unit' => $row->product?->unit ?? 'pcs',
                    'estimated_cost' => $estimatedCost,
                ];
            }
        }

        ksort($dailyRecommendations);
        foreach ($dailyRecommendations as &$day) {
            usort($day['items'], static fn (array $a, array $b): int => $b['qty'] <=> $a['qty']);
        }
        unset($day);

        $this->dailyRecommendations = array_values($dailyRecommendations);
    }

    public function checkMlService(): void
    {
        $status = app(MlService::class)->getStatus();

        Notification::make()
            ->title(($status['healthy'] ?? false) ? 'ML Service online' : 'ML Service offline')
            ->body('URL: ' . ($status['api_url'] ?? '-') . ' | Timeout: ' . ($status['timeout'] ?? '-') . 's')
            ->color(($status['healthy'] ?? false) ? 'success' : 'danger')
            ->send();
    }
}
