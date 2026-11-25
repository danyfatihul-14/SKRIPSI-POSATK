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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\WithPagination;

class ProductCatalog extends Page
{
    use WithPagination;

    protected static string $resource = ProductResource::class;
    protected static string $view = 'filament.resources.product-resource.pages.product-catalog';
    protected static ?string $title = 'Product';

    public $storeId = null;
    public array $stores = [];
    public string $search = '';
    public string $missingFilter = ''; // '' | 'incomplete' | 'no_image' | 'no_category' | 'no_purchase_price'
    public array $selectedProductIds = [];
    public bool $selectAllOnPage = false;

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
        $this->selectedProductIds = [];
        $this->selectAllOnPage = false;
        $this->resetPage();
    }

    public function updatedMissingFilter(): void
    {
        $this->selectedProductIds = [];
        $this->selectAllOnPage = false;
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->selectedProductIds = [];
        $this->selectAllOnPage = false;
        $this->resetPage();
    }

    public function updatedSelectAllOnPage($value): void
    {
        if ($value) {
            $viewData = $this->getViewData();
            $products = $viewData['products'];
            $this->selectedProductIds = $products->pluck('product_id')->map(fn($id) => (string) $id)->toArray();
        } else {
            $this->selectedProductIds = [];
        }
    }

    public function updatedSelectedProductIds(): void
    {
        $viewData = $this->getViewData();
        $products = $viewData['products'];
        $pageIds = $products->pluck('product_id')->map(fn($id) => (string) $id)->toArray();

        $allSelected = count($pageIds) > 0 && empty(array_diff($pageIds, $this->selectedProductIds));
        $this->selectAllOnPage = $allSelected;
    }

    public function deleteSelectedProducts(): void
    {
        if (empty($this->selectedProductIds)) {
            Notification::make()->warning()->title('Pilih setidaknya satu produk untuk dihapus')->send();
            return;
        }

        $ids = array_values(array_map('intval', array_filter($this->selectedProductIds)));

        if (empty($ids)) {
            Notification::make()->warning()->title('Tidak ada produk valid yang dipilih')->send();
            return;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS = 0;');

        $products = Product::whereIn('product_id', $ids)->get();
        $count = $products->count();

        $imagePaths = $products->pluck('file_url')->filter()->toArray();

        DB::table('stock_levels')->whereIn('product_id', $ids)->delete();
        DB::table('stock_logs')->whereIn('product_id', $ids)->delete();
        DB::table('ml_predictions')->whereIn('product_id', $ids)->delete();
        DB::table('transaction_items')->whereIn('product_id', $ids)->delete();
        
        Product::whereIn('product_id', $ids)->delete();

        DB::statement('SET FOREIGN_KEY_CHECKS = 1;');

        foreach ($imagePaths as $path) {
            if (! empty($path)) {
                $isUsed = Product::where('file_url', $path)->exists();
                if (! $isUsed) {
                    Storage::disk('public')->delete($path);
                }
            }
        }

        $this->selectedProductIds = [];
        $this->selectAllOnPage = false;

        Notification::make()
            ->success()
            ->title("Berhasil menghapus {$count} produk terpilih")
            ->send();
    }

    public function mount(): void
    {
        // Ambil list store untuk dropdown filter
        $this->stores = Store::query()->pluck('name_store', 'store_id')->toArray();
        // Ambil store_id dari user aktif atau store pertama
        $this->storeId = Auth::user()->store_id ?? Store::query()->value('store_id');
    }

    protected function getViewData(): array
    {
        $currentStoreId = (!empty($this->storeId)) ? (int) $this->storeId : null;

        // Fast DB level aggregate count in a single query (~1ms)
        $stats = DB::table('products')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.category_id')
            ->when($currentStoreId, function ($q) use ($currentStoreId) {
                $q->whereExists(function ($sub) use ($currentStoreId) {
                    $sub->select(DB::raw(1))
                        ->from('stock_levels')
                        ->whereColumn('stock_levels.product_id', 'products.product_id')
                        ->where('stock_levels.store_id', $currentStoreId);
                });
            })
            ->selectRaw("
                SUM(CASE WHEN products.file_url IS NULL OR products.file_url = '' THEN 1 ELSE 0 END) as count_no_image,
                SUM(CASE WHEN products.category_id IS NULL THEN 1 ELSE 0 END) as count_no_category,
                SUM(CASE WHEN (products.purchase_price IS NULL OR products.purchase_price <= 0) AND (categories.is_dynamic_price = 0 OR categories.is_dynamic_price IS NULL) THEN 1 ELSE 0 END) as count_no_purchase_price,
                SUM(CASE WHEN (products.file_url IS NULL OR products.file_url = '') OR products.category_id IS NULL OR ((products.purchase_price IS NULL OR products.purchase_price <= 0) AND (categories.is_dynamic_price = 0 OR categories.is_dynamic_price IS NULL)) THEN 1 ELSE 0 END) as count_incomplete
            ")
            ->first();

        $countNoImage = (int) ($stats->count_no_image ?? 0);
        $countNoCategory = (int) ($stats->count_no_category ?? 0);
        $countNoPurchasePrice = (int) ($stats->count_no_purchase_price ?? 0);
        $countIncomplete = (int) ($stats->count_incomplete ?? 0);

        // Base Product Query
        $query = Product::query()
            ->with(['category', 'stockLevels' => function ($q) use ($currentStoreId) {
                if ($currentStoreId) {
                    $q->where('store_id', $currentStoreId);
                }
            }]);

        // Filter: hanya tampilkan produk yang TERDAFTAR di cabang yang dipilih
        // (punya record stock_levels untuk store_id tersebut, berapapun stoknya termasuk 0)
        if ($currentStoreId) {
            $query->whereHas('stockLevels', function ($q) use ($currentStoreId) {
                $q->where('store_id', $currentStoreId);
            });
        }

        if (!empty($this->search)) {
            $q = trim($this->search);
            $query->where(function ($sub) use ($q) {
                $sub->where('product_name', 'like', "%{$q}%")
                    ->orWhere('variant', 'like', "%{$q}%")
                    ->orWhereHas('category', function ($catQuery) use ($q) {
                        $catQuery->where('category_name', 'like', "%{$q}%");
                    });
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

        $paginatedProducts = $query->orderBy('product_name')->paginate(50);


        // Transform paginated items safely
        $paginatedProducts->getCollection()->transform(function ($product) use ($currentStoreId) {
            $stockLevel = $currentStoreId
                ? $product->stockLevels->firstWhere('store_id', $currentStoreId)
                : $product->stockLevels->first();
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

            $product->formatted_stock = (int) ($stockLevel?->quantity ?? 0);
            $product->formatted_discount = (float) ($stockLevel?->discount ?? 0);
            if ($stockLevel?->purchase_price && $stockLevel->purchase_price > 0) {
                $product->purchase_price = $stockLevel->purchase_price;
            }
            if ($stockLevel?->unit_price && $stockLevel->unit_price > 0) {
                $product->selling_price = $stockLevel->unit_price;
            }
            $product->missing_tags = $missing;
            $product->is_dynamic = $isDynamic;

            return $product;
        });

        return [
            'products' => $paginatedProducts,
            'countNoImage' => $countNoImage,
            'countNoCategory' => $countNoCategory,
            'countNoPurchasePrice' => $countNoPurchasePrice,
            'countIncomplete' => $countIncomplete,
        ];
    }

    public function deleteProduct(int|string $productId): void
    {
        $productId = (int) $productId;
        $product = Product::query()->find($productId);

        if (! $product) {
            Notification::make()->danger()->title('Produk tidak ditemukan')->send();
            return;
        }

        $currentStoreId = $this->storeId ?? Auth::user()?->store_id;

        if ($currentStoreId) {
            // Jika filter cabang aktif: hapus HANYA StockLevel cabang ini
            DB::table('stock_levels')
                ->where('product_id', $productId)
                ->where('store_id', $currentStoreId)
                ->delete();

            // Cek apakah masih ada cabang lain yang punya stok produk ini
            $stillUsedByOtherStores = DB::table('stock_levels')
                ->where('product_id', $productId)
                ->exists();

            if (! $stillUsedByOtherStores) {
                // Tidak ada cabang lain yang menggunakan produk ini, hapus master + data terkait
                $imagePath = $product->file_url;

                DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
                DB::table('stock_logs')->where('product_id', $productId)->delete();
                DB::table('ml_predictions')->where('product_id', $productId)->delete();
                DB::table('transaction_items')->where('product_id', $productId)->delete();
                $product->delete();
                DB::statement('SET FOREIGN_KEY_CHECKS = 1;');

                if (! empty($imagePath)) {
                    $isUsedByOther = Product::query()->where('file_url', $imagePath)->exists();
                    if (! $isUsedByOther) {
                        Storage::disk('public')->delete($imagePath);
                    }
                }
            }

            Notification::make()->success()->title('Produk berhasil dihapus dari cabang ini')->send();
        } else {
            // Tidak ada filter cabang: hapus master produk + semua stok di semua cabang
            $imagePath = $product->file_url;

            DB::statement('SET FOREIGN_KEY_CHECKS = 0;');
            DB::table('stock_levels')->where('product_id', $productId)->delete();
            DB::table('stock_logs')->where('product_id', $productId)->delete();
            DB::table('ml_predictions')->where('product_id', $productId)->delete();
            DB::table('transaction_items')->where('product_id', $productId)->delete();
            $product->delete();
            DB::statement('SET FOREIGN_KEY_CHECKS = 1;');

            if (! empty($imagePath)) {
                $isUsedByOther = Product::query()->where('file_url', $imagePath)->exists();
                if (! $isUsedByOther) {
                    Storage::disk('public')->delete($imagePath);
                }
            }

            Notification::make()->success()->title('Produk berhasil dihapus')->send();
        }
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
