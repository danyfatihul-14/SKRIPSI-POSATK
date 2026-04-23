<?php
// filepath: e:\Polinema\Semester8\Skripsi\pos\app\Filament\Resources\ProductResource\Pages\EditProduct.php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\StockLevel;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load stok dari toko pertama yang ada
        $stockLevel = StockLevel::where('product_id', $this->record->product_id)
            ->first();

        if ($stockLevel) {
            $data['initial_store_id'] = $stockLevel->store_id;
            $data['initial_stock'] = $stockLevel->quantity;
            $data['initial_discount'] = $stockLevel->discount;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $storeId = $this->data['initial_store_id'] ?? null;
        $stock = $this->data['initial_stock'] ?? null;
        $discount = $this->data['initial_discount'] ?? 0;

        if (!$storeId || $stock === null) {
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
            ->title('Produk & stok berhasil diupdate')
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
