<?php
// filepath: e:\Polinema\Semester8\Skripsi\pos\app\Filament\Resources\ProductResource\Pages\CreateProduct.php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\StockLevel;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function afterCreate(): void
    {
        $storeId = $this->data['initial_store_id'] ?? null;
        $stock = $this->data['initial_stock'] ?? 0;
        $discount = $this->data['initial_discount'] ?? 0;

        if (!$storeId) {
            Notification::make()
                ->warning()
                ->title('Stok tidak tersimpan')
                ->body('Silakan pilih toko terlebih dahulu.')
                ->send();
            return;
        }

        StockLevel::updateOrCreate(
            [
                'store_id'   => (int) $storeId,
                'product_id' => $this->record->product_id,
            ],
            [
                'quantity'   => max(0, (int) $stock),
                'unit_price' => (float) $this->record->selling_price,
                'discount'   => max(0, (float) $discount),
            ]
        );

        Notification::make()
            ->success()
            ->title('Produk & stok berhasil ditambahkan')
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
