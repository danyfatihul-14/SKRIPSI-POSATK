<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Product;

class CetakBarcode extends Page
{
    protected static ?string $navigationLabel = 'Cetak Barcode';
    protected static ?string $navigationIcon = 'heroicon-o-qr-code';
    protected static ?int $navigationSort = 6;
    protected static string $view = 'filament.pages.cetak-barcode';

    public function getViewData(): array
    {
        $storeId = auth()->user()?->store_id;
        $query = Product::query();
        if ($storeId) {
            $query->whereHas('stockLevels', function ($subQuery) use ($storeId) {
                $subQuery->where('store_id', $storeId);
            });
        }
        
        $products = $query->get()->map(function ($product) {
            return [
                'product_id'    => $product->product_id,
                'product_name'  => $product->product_name,
                'variant'       => $product->variant,
                'unit'          => $product->unit,
            ];
        })->toArray();

        return [
            'products' => $products,
        ];
    }
}
