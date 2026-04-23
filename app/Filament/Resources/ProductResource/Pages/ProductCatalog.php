<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\Product;
use App\Models\Store;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Filament\Actions\Action;


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
        $query = Product::query()
            ->with([
                'category',
                'stockLevels' => fn($q) => $q->where('store_id', $this->storeId ?? 0)
            ]);

        if ($this->search) {
            $query->where('product_name', 'like', '%' . $this->search . '%');
        }

        $this->products = $query->get()->map(function ($product) {
            $stockLevel = $product->stockLevels->first();

            return [
                'product_id' => $product->product_id,
                'product_name' => $product->product_name,
                'category_name' => $product->category?->category_name ?? '-',
                'file_url' => $product->file_url,
                'selling_price' => $product->selling_price,
                'purchase_price' => $product->purchase_price,
                'stock' => $stockLevel ? $stockLevel->quantity : 0,
                'unit_price' => $stockLevel ? $stockLevel->unit_price : $product->selling_price,
                'discount' => $stockLevel ? $stockLevel->discount : 0,
            ];
        })->toArray();
    }

    public function updatedSearch(): void
    {
        $this->loadProducts();
    }
}
