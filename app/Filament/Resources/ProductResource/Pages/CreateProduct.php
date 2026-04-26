<?php
namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\Product;
use App\Models\StockLevel;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected int $createdCount = 0;

    protected function handleRecordCreation(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $storeId = $data['initial_store_id'] ?? null;

            $variants = $data['variants'] ?? [[
                'purchase_price'   => $data['purchase_price'] ?? 0,
                'selling_price'    => $data['selling_price'] ?? 0,
                'unit'             => $data['unit'] ?? 'biji',
                'variant'          => $data['variant'] ?? null,
                'initial_stock'    => $data['initial_stock'] ?? 0,
                'initial_discount' => $data['initial_discount'] ?? 0,
            ]];

            $baseImage = $data['file_url'] ?? null;

            $firstProduct = null;

            foreach ($variants as $i => $variant) {
                $imagePath = $baseImage;

                // varian kedua dan seterusnya: copy file agar path berbeda
                if ($i > 0 && ! empty($baseImage) && Storage::disk('public')->exists($baseImage)) {
                    $ext = pathinfo($baseImage, PATHINFO_EXTENSION);
                    $copiedPath = 'products/' . Str::ulid() . ($ext ? ".{$ext}" : '');
                    Storage::disk('public')->copy($baseImage, $copiedPath);
                    $imagePath = $copiedPath;
                }

                $product = Product::create([
                    'product_name'    => $data['product_name'],
                    'category_id'     => $data['category_id'] ?? null,
                    'file_url'        => $imagePath,
                    'purchase_price'  => (float) ($variant['purchase_price'] ?? 0),
                    'selling_price'   => (float) ($variant['selling_price'] ?? 0),
                    'unit'            => $variant['unit'] ?? 'biji',
                    'variant'         => $variant['variant'] ?? null,
                ]);

                if ($storeId) {
                    StockLevel::updateOrCreate(
                        [
                            'store_id'   => (int) $storeId,
                            'product_id' => $product->product_id,
                        ],
                        [
                            'quantity'   => max(0, (int) ($variant['initial_stock'] ?? 0)),
                            'unit_price' => (float) ($variant['selling_price'] ?? 0),
                            'discount'   => max(0, (float) ($variant['initial_discount'] ?? 0)),
                        ]
                    );
                }

                $this->createdCount++;
                $firstProduct ??= $product;
            }

            return $firstProduct;
        });
    }

    protected function afterCreate(): void
    {
        Notification::make()
            ->success()
            ->title("Berhasil menambahkan {$this->createdCount} varian produk")
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}