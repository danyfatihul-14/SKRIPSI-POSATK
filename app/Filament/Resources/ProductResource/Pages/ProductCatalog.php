<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\Product;
use App\Models\Store;
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
    public $products = [];
    public $search = '';

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

    public function mount(): void
    {
        // Ambil store_id dari user aktif atau store pertama
        $this->storeId = Auth::user()->store_id ?? Store::query()->value('store_id');

        $this->loadProducts();
    }

    public function loadProducts(): void
    {
        $query = Product::query()->with('category', 'stockLevels');

        if ($this->search) {
            $query->where('product_name', 'like', '%' . $this->search . '%');
        }

        $this->products = $query->get()->map(function ($product) {
            $stockLevel = $product->stockLevels->first();

            return [
                'product_id'     => $product->product_id,
                'product_name'   => $product->product_name,
                'category_name'  => $product->category?->category_name ?? '-',
                'file_url'       => $product->file_url ?? null,
                'selling_price'  => (float) ($product->selling_price ?? 0),
                'purchase_price' => (float) ($product->purchase_price ?? 0),
                'stock'          => (int) ($stockLevel?->quantity ?? 0),
                'unit_price'     => (float) ($stockLevel?->unit_price ?? $product->selling_price ?? 0),
                'discount'       => (float) ($stockLevel?->discount ?? 0),
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

        $product->delete();

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
}
