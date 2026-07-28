<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\MlPrediction;
use App\Models\Product;
use App\Models\Store;
use App\Models\TransactionItem;
use App\Services\MlService;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class ProductCatalog extends Page
{
    protected static string $resource = ProductResource::class;
    protected static string $view = 'filament.resources.product-resource.pages.product-catalog';
    protected static ?string $title = 'Product';

    public ?int $storeId = null;
    public array $stores = [];
    public $products = [];
    public $search = '';
    public string $missingFilter = ''; // '' | 'incomplete' | 'no_image' | 'no_category' | 'no_purchase_price'
    public int $countNoImage = 0;
    public int $countNoCategory = 0;
    public int $countNoPurchasePrice = 0;
    public int $countIncomplete = 0;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('create')
                ->label('New Product')
                ->url(ProductResource::getUrl('create'))
                ->color('primary')
                ->icon('heroicon-o-plus'),
        ];
    }
    
    public function updatedStoreId(): void
    {
        $this->loadProducts();
    }

    public function updatedMissingFilter(): void
    {
        $this->loadProducts();
    }

    public function mount(): void
    {
        // Ambil list store untuk dropdown filter
        $this->stores = Store::query()->pluck('name_store', 'store_id')->toArray();
        // Ambil store_id dari user aktif atau store pertama
        $this->storeId = Auth::user()->store_id ?? Store::query()->value('store_id');

        $this->loadProducts();
    }

    public function loadProducts(): void
    {
        $baseQuery = \App\Models\Product::query()->with('category');
        if ($this->storeId) {
            $baseQuery->whereHas('stockLevels', function ($q) {
                $q->where('store_id', $this->storeId);
            });
        }

        $allProds = $baseQuery->get();
        $this->countNoImage = $allProds->filter(fn($p) => empty($p->file_url))->count();
        $this->countNoCategory = $allProds->filter(fn($p) => empty($p->category_id))->count();
        $this->countNoPurchasePrice = $allProds->filter(function ($p) {
            $isDynamic = (bool) ($p->category?->is_dynamic_price ?? false);
            return ! $isDynamic && (empty($p->purchase_price) || $p->purchase_price <= 0);
        })->count();

        $this->countIncomplete = $allProds->filter(function ($p) {
            $isDynamic = (bool) ($p->category?->is_dynamic_price ?? false);
            $hasNoImage = empty($p->file_url);
            $hasNoCategory = empty($p->category_id);
            $hasNoPurchasePrice = ! $isDynamic && (empty($p->purchase_price) || $p->purchase_price <= 0);

            return $hasNoImage || $hasNoCategory || $hasNoPurchasePrice;
        })->count();

        $query = \App\Models\Product::query()
            ->with(['category', 'stockLevels' => function ($q) {
                if ($this->storeId) {
                    $q->where('store_id', $this->storeId);
                }
            }]);

        // Filter produk yang ada di store yang dipilih
        if ($this->storeId) {
            $query->whereHas('stockLevels', function ($q) {
                $q->where('store_id', $this->storeId);
            });
        }

        if (!empty($this->search)) {
            $q = $this->search;
            $query->where(function ($sub) use ($q) {
                $sub->where('product_name', 'like', "%{$q}%")
                    ->orWhere('variant', 'like', "%{$q}%");
            });
        }

        if ($this->missingFilter === 'no_image') {
            $query->where(function ($q) {
                $q->whereNull('file_url')->orWhere('file_url', '');
            });
        } elseif ($this->missingFilter === 'no_category') {
            $query->whereNull('category_id');
        } elseif ($this->missingFilter === 'no_purchase_price') {
            $query->where(function ($q) {
                $q->whereNull('purchase_price')->orWhere('purchase_price', '<=', 0);
            })->where(function ($q) {
                $q->whereHas('category', function ($cat) {
                    $cat->where('is_dynamic_price', false);
                })->orWhereNull('category_id');
            });
        } elseif ($this->missingFilter === 'incomplete') {
            $query->where(function ($q) {
                $q->whereNull('file_url')
                  ->orWhere('file_url', '')
                  ->orWhereNull('category_id')
                  ->orWhere(function ($sub) {
                      $sub->where(function ($pPrice) {
                          $pPrice->whereNull('purchase_price')->orWhere('purchase_price', '<=', 0);
                      })->whereHas('category', function ($cat) {
                          $cat->where('is_dynamic_price', false);
                      });
                  });
            });
        }

        $this->products = $query->get()->map(function ($product) {
            $stockLevel = $product->stockLevels->first();
            $isDynamic = (bool) ($product->category?->is_dynamic_price ?? false);

            $missing = [];
            if (empty($product->file_url)) {
                $missing[] = 'Tanpa Gambar';
            }
            if (empty($product->category_id)) {
                $missing[] = 'Tanpa Kategori';
            }
            if (! $isDynamic && (empty($product->purchase_price) || $product->purchase_price <= 0)) {
                $missing[] = 'Tanpa Harga Beli';
            }

            return [
                'product_id'     => $product->product_id,
                'product_name'   => $product->product_name,
                'variant'        => $product->variant ?: '-',
                'unit'           => $product->unit ?? '-',
                'category_name'  => $product->category?->category_name ?? null,
                'category_id'    => $product->category_id,
                'is_dynamic'     => $isDynamic,
                'file_url'       => $product->file_url,
                'purchase_price' => (float) ($product->purchase_price ?? 0),
                'selling_price'  => (float) ($product->selling_price ?? 0),
                'stock'          => (int) ($stockLevel?->quantity ?? 0),
                'discount'       => (float) ($stockLevel?->discount ?? 0),
                'missing'        => $missing,
            ];
        })->toArray();
    }

    public function deleteProduct(int|string $productId): void
    {
        $product = Product::query()->find($productId);

        if (! $product) {
            Notification::make()->danger()->title('Produk tidak ditemukan')->send();
            return;
        }

        $imagePath = $product->file_url;

        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
        $product->delete();
        \Illuminate\Support\Facades\DB::statement('SET FOREIGN_KEY_CHECKS = 1;');

        // hapus file jika tidak dipakai produk lain
        if (! empty($imagePath)) {
            $isUsedByOther = Product::query()
                ->where('file_url', $imagePath)
                ->exists();

            if (! $isUsedByOther) {
                Storage::disk('public')->delete($imagePath);
            }
        }

        Notification::make()->success()->title('Produk berhasil dihapus')->send();

        // kalau punya method refresh list:
        if (method_exists($this, 'loadProducts')) {
            $this->loadProducts();
        }
    }

    public function updatedSearch(): void
    {
        $this->loadProducts();
    }

    public function predictProduct(int|string $productId): void
    {
        $product = Product::query()->with('category')->find($productId);

        if (! $product) {
            Notification::make()->danger()->title('Produk tidak ditemukan')->send();
            return;
        }

        $storeId = $this->storeId ?? Auth::user()?->store_id ?? Store::query()->value('store_id');

        if (! $storeId) {
            Notification::make()->danger()->title('Store tidak ditemukan')->send();
            return;
        }

        $store = Store::query()->find($storeId);
        if (! $store) {
            Notification::make()->danger()->title('Store tidak valid')->send();
            return;
        }

        try {
            $lag1 = $this->getLag1((int) $product->product_id, (int) $store->store_id);
            $lag7 = $this->getLag7((int) $product->product_id, (int) $store->store_id);

            $mlResult = app(MlService::class)->predict(
                $product->product_name,
                $product->category?->category_name ?? 'Uncategorized',
                $product->unit ?? 'pcs',
                $lag1,
                $lag7,
                7
            );

            $prediksiHarian = $mlResult['prediksi_harian'] ?? [];
            $startDate = $prediksiHarian[0]['tanggal'] ?? null;
            $endDate = $prediksiHarian[count($prediksiHarian) - 1]['tanggal'] ?? null;

            $prediction = MlPrediction::create([
                'store_id' => $store->store_id,
                'product_id' => $product->product_id,
                'prediction_date' => now()->toDateString(),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'selling_price' => $product->selling_price ?? 0,
                'predicted_revenue' => ($mlResult['total_prediksi'] ?? 0) * ($product->selling_price ?? 0),
                'predicted_quantity' => (int) ($mlResult['total_prediksi'] ?? 0),
                'confidence_level' => 0.85,
                'featured_used' => [
                    'lag_1' => $lag1,
                    'lag_7' => $lag7,
                    'days' => 7,
                ],
                'model_version' => '1.0',
            ]);

            Notification::make()
                ->success()
                ->title('Prediksi berhasil')
                ->body('Total prediksi: ' . number_format((int) ($mlResult['total_prediksi'] ?? 0), 0, ',', '.') . ' unit | ID: #' . $prediction->ml_id)
                ->send();
        } catch (\Throwable $e) {
            Notification::make()
                ->danger()
                ->title('Prediksi gagal')
                ->body($e->getMessage())
                ->send();
        }
    }

    private function getLag1(int $productId, int $storeId): int
    {
        return (int) TransactionItem::query()
            ->where('product_id', $productId)
            ->whereHas('transaction', function ($query) use ($storeId): void {
                $query->where('store_id', $storeId)
                    ->whereDate('created_at', now()->subDay());
            })
            ->sum('quantity');
    }

    private function getLag7(int $productId, int $storeId): int
    {
        return (int) TransactionItem::query()
            ->where('product_id', $productId)
            ->whereHas('transaction', function ($query) use ($storeId): void {
                $query->where('store_id', $storeId)
                    ->whereDate('created_at', '>=', now()->subDays(7));
            })
            ->sum('quantity');
    }
}
