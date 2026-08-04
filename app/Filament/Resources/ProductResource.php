<?php
// filepath: e:\Polinema\Semester8\Skripsi\pos\app\Filament\Resources\ProductResource.php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use App\Models\Store;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Product';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('product_name')
                ->label('Nama Produk')
                ->maxLength(150)
                ->required(),

            Forms\Components\Select::make('category_id')
                ->label('Kategori')
                ->relationship('category', 'category_name')
                ->searchable()
                ->preload()
                ->live(),

            Forms\Components\FileUpload::make('file_url')
                ->label('Foto Produk')
                ->image()
                ->acceptedFileTypes(['image/*'])
                ->disk('public')
                ->directory('products')
                ->maxSize(10240)
                ->maxFiles(1)
                ->saveUploadedFileUsing(function (TemporaryUploadedFile $file): string {
                    $extension = 'jpg';
                    $path = 'products/' . Str::ulid() . '.' . $extension;
                
                    $contents = file_get_contents($file->getRealPath());
                
                    $image = @imagecreatefromstring($contents);
                
                    if ($image === false) {
                        Storage::disk('public')->put($path, $contents);
                        return $path;
                    }
                
                    $width = imagesx($image);
                    $height = imagesy($image);
                
                    $maxWidth = 800;
                    $maxHeight = 800;
                
                    $ratio = min($maxWidth / $width, $maxHeight / $height, 1);
                    $newWidth = (int) ($width * $ratio);
                    $newHeight = (int) ($height * $ratio);
                
                    $resized = imagescale($image, $newWidth, $newHeight);
                    imagedestroy($image);
                
                    if ($resized === false) {
                        Storage::disk('public')->put($path, $contents);
                        return $path;
                    }
                
                    ob_start();
                    imagejpeg($resized, null, 40);
                    $final = ob_get_clean();
                
                    imagedestroy($resized);
                
                    Storage::disk('public')->put($path, $final);
                
                    return $path;
                })
                ->helperText('Foto otomatis dikompres (low quality, max 2MB)')
                ->extraInputAttributes([
                    'accept' => 'image/*',
                    'capture' => 'environment',
                ]),

            Forms\Components\Select::make('initial_store_id')
                ->label('Toko')
                ->options(fn() => Store::query()->pluck('name_store', 'store_id'))
                ->searchable()
                ->preload()
                ->live()
                ->afterStateUpdated(function ($state, Forms\Set $set, $record) {
                    if (! $record || ! $state) return;
                    $stockLevel = \App\Models\StockLevel::where('product_id', $record->product_id)
                        ->where('store_id', (int) $state)
                        ->first();
                    $set('initial_stock', $stockLevel ? (int) $stockLevel->quantity : 0);
                    $set('initial_discount', $stockLevel ? (float) $stockLevel->discount : 0);
                })
                ->required(fn(string $operation) => $operation === 'create')
                ->dehydrated(true),

            // Dipakai saat CREATE (multi baris)
            Forms\Components\Repeater::make('variants')
                ->label('Detail Variasi')
                ->visible(fn(string $operation) => $operation === 'create')
                ->required(fn(string $operation) => $operation === 'create')
                ->minItems(1)
                ->defaultItems(1)
                ->addActionLabel('Tambah Variasi')
                ->columnSpanFull()
                ->grid([
                    'default' => 1,
                    'xl' => 2,
                ])
                ->schema([
                    Forms\Components\TextInput::make('purchase_price')
                        ->label('Harga Beli')
                        ->numeric()
                        ->prefix('Rp')
                        ->default(0)
                        ->nullable()
                        ->placeholder('0 (opsional untuk harga dinamis)'),

                    Forms\Components\TextInput::make('selling_price')
                        ->label('Harga Jual')
                        ->numeric()
                        ->prefix('Rp')
                        ->default(0)
                        ->nullable()
                        ->placeholder('0 (opsional untuk harga dinamis)'),

                    Forms\Components\Select::make('unit')
                        ->label('Satuan')
                        ->options([
                            'biji' => 'Biji',
                            'pcs' => 'Pcs',
                            'lusin' => 'Lusin',
                            'pack' => 'Pack',
                            'dus' => 'Dus',
                            'rim' => 'Rim',
                            'pak' => 'Pak',
                            'box' => 'Box',
                            'rol' => 'Rol',
                            'set' => 'Set',
                            'lembar' => 'Lembar',
                            'kg' => 'Kg',
                            'gram' => 'Gram',
                            'btl' => 'Botol',
                            'porsi' => 'Porsi',
                        ])
                        ->required()
                        ->default('biji'),

                    Forms\Components\TextInput::make('variant')
                        ->label('Varian Isi')
                        ->placeholder('contoh: 50 Lembar')
                        ->maxLength(100),

                    Forms\Components\TextInput::make('initial_stock')
                        ->label('Stok Awal')
                        ->numeric()
                        ->minValue(0)
                        ->default(0)
                        ->nullable()
                        ->placeholder('0 (opsional)'),

                    Forms\Components\TextInput::make('initial_discount')
                        ->label('Diskon Awal')
                        ->numeric()
                        ->minValue(0)
                        ->default(0),
                ])
                ->columns(2),

            Forms\Components\TextInput::make('purchase_price')
                ->label('Harga Beli')
                ->numeric()
                ->prefix('Rp')
                ->default(0)
                ->nullable()
                ->visible(fn(string $operation) => $operation === 'edit'),

            Forms\Components\TextInput::make('selling_price')
                ->label('Harga Jual')
                ->numeric()
                ->prefix('Rp')
                ->default(0)
                ->nullable()
                ->visible(fn(string $operation) => $operation === 'edit'),

            Forms\Components\Select::make('unit')
                ->label('Satuan')
                ->options(function ($record) {
                    $units = [
                        'biji' => 'Biji',
                        'pcs' => 'Pcs',
                        'lusin' => 'Lusin',
                        'pack' => 'Pack',
                        'dus' => 'Dus',
                        'rim' => 'Rim',
                        'pak' => 'Pak',
                        'box' => 'Box',
                        'rol' => 'Rol',
                        'set' => 'Set',
                        'lembar' => 'Lembar',
                        'kg' => 'Kg',
                        'gram' => 'Gram',
                        'btl' => 'Botol',
                        'porsi' => 'Porsi',
                    ];
                    if ($record && ! empty($record->unit) && ! isset($units[$record->unit])) {
                        $units[$record->unit] = ucfirst($record->unit);
                    }
                    return $units;
                })
                ->searchable()
                ->required(fn(string $operation) => $operation === 'edit')
                ->visible(fn(string $operation) => $operation === 'edit'),

            Forms\Components\TextInput::make('variant')
                ->label('Varian Isi')
                ->placeholder('contoh: 50 lembar')
                ->maxLength(100)
                ->visible(fn(string $operation) => $operation === 'edit'),

            Forms\Components\TextInput::make('initial_stock')
                ->label('Jumlah Stok')
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->nullable()
                ->visible(fn(string $operation) => $operation === 'edit'),

            Forms\Components\TextInput::make('initial_discount')
                ->label('Diskon (%)')
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->nullable()
                ->visible(fn(string $operation) => $operation === 'edit'),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ProductCatalog::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
