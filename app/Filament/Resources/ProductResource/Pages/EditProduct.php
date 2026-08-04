<?php
// filepath: e:\Polinema\Semester8\Skripsi\pos\app\Filament\Resources\ProductResource\Pages\EditProduct.php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\StockLevel;
use App\Models\StockLog;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected ?string $oldFileUrl = null;
    protected ?int $oldStoreId = null;
    protected ?int $oldStockQty = null;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->oldFileUrl = $this->record->file_url;

        // Cek store_id dari URL parameter atau dari user aktif
        $preferredStoreId = request()->query('store_id') ?: auth()->user()?->store_id;

        $stockLevel = null;
        if ($preferredStoreId) {
            $stockLevel = StockLevel::where('product_id', $this->record->product_id)
                ->where('store_id', (int) $preferredStoreId)
                ->first();
        }

        // Jika tidak ada di store terpilih, ambil stok level pertama yang ada untuk produk ini
        if (! $stockLevel) {
            $stockLevel = StockLevel::where('product_id', $this->record->product_id)->first();
        }

        if ($stockLevel) {
            $data['initial_store_id'] = (int) $stockLevel->store_id;
            $data['initial_stock'] = (int) $stockLevel->quantity;
            $data['initial_discount'] = (float) $stockLevel->discount;
            $this->oldStoreId = (int) $stockLevel->store_id;
            $this->oldStockQty = (int) $stockLevel->quantity;
        } else {
            $defaultStoreId = (int) ($preferredStoreId ?: \App\Models\Store::query()->value('store_id'));
            $data['initial_store_id'] = $defaultStoreId;
            $data['initial_stock'] = 0;
            $data['initial_discount'] = 0;
            $this->oldStoreId = null;
            $this->oldStockQty = 0;
        }

        // Pastikan seluruh data atribut produk terisi sempurna di form edit
        $data['product_name']   = $this->record->product_name;
        $data['category_id']    = $this->record->category_id;
        $data['file_url']       = $this->record->file_url;
        $data['purchase_price'] = (float) ($this->record->purchase_price ?? 0);
        $data['selling_price']  = (float) ($this->record->selling_price ?? 0);
        $data['unit']           = $this->record->unit ?? 'biji';
        $data['variant']        = $this->record->variant ?? '';

        return $data;
    }

    protected function afterSave(): void
    {
        $newFileUrl = $this->record->file_url;

        if ($this->oldFileUrl && $this->oldFileUrl !== $newFileUrl) {
            $disk = 'public';

            if (Storage::disk($disk)->exists($this->oldFileUrl)) {
                Storage::disk($disk)->delete($this->oldFileUrl);
            }
        }

        $newStoreId = isset($this->data['initial_store_id']) ? (int) $this->data['initial_store_id'] : null;
        $stock = max(0, (int) ($this->data['initial_stock'] ?? 0));
        $discount = max(0, (float) ($this->data['initial_discount'] ?? 0));

        if ($newStoreId) {
            // Jika toko diubah (oldStoreId beda dengan newStoreId), hapus record stok lama agar tidak menumpuk/duplikat
            if ($this->oldStoreId && $this->oldStoreId !== $newStoreId) {
                StockLevel::where('product_id', $this->record->product_id)
                    ->where('store_id', $this->oldStoreId)
                    ->delete();
            }

            $stockLevel = StockLevel::updateOrCreate(
                [
                    'store_id'   => $newStoreId,
                    'product_id' => $this->record->product_id,
                ],
                [
                    'quantity'   => $stock,
                    'unit_price' => (float) $this->record->selling_price,
                    'discount'   => $discount,
                ]
            );

            // Log perubahan stok jika ada perubahan stok
            if ($this->oldStockQty !== null && $this->oldStockQty !== $stock) {
                StockLog::create([
                    'store_id'        => $newStoreId,
                    'product_id'      => $this->record->product_id,
                    'user_id'         => auth()->user()?->user_id,
                    'type'            => 'adjustment',
                    'old_quantity'    => $this->oldStockQty,
                    'new_quantity'    => $stock,
                    'quantity_change' => $stock - $this->oldStockQty,
                    'notes'           => 'Edit stok via ProductResource oleh ' . (auth()->user()?->name ?? 'System'),
                ]);
            }
        }

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

