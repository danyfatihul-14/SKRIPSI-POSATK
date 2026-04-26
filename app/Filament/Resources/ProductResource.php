<?php
// filepath: e:\Polinema\Semester8\Skripsi\pos\app\Filament\Resources\ProductResource.php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use App\Models\Store;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;

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
                ->preload(),

            Forms\Components\FileUpload::make('file_url')
                ->label('Foto Produk')
                ->image()
                ->acceptedFileTypes(['image/*'])
                ->disk('public')
                ->directory('products')
                ->maxSize(2048)
                ->maxFiles(1)
                ->extraInputAttributes([
                    'accept' => 'image/*',
                    'capture' => 'environment',
                ]),

            Forms\Components\Select::make('initial_store_id')
                ->label('Toko')
                ->options(fn() => Store::query()->pluck('name_store', 'store_id'))
                ->searchable()
                ->required(fn(string $operation) => $operation === 'create')
                ->visible(fn(string $operation) => $operation === 'create')
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
                        ->required(),

                    Forms\Components\TextInput::make('selling_price')
                        ->label('Harga Jual')
                        ->numeric()
                        ->prefix('Rp')
                        ->required(),

                    Forms\Components\Select::make('unit')
                        ->label('Satuan')
                        ->options([
                            'biji' => 'Biji',
                            'lusin' => 'Lusin',
                            'pack' => 'Pack',
                            'dus' => 'Dus',
                            'rim' => 'Rim',
                            'pak' => 'Pak',
                            'box' => 'Box',
                            'rol' => 'Rol',
                            'set' => 'Set',
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
                        ->required(),

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
                ->required(fn(string $operation) => $operation === 'edit')
                ->visible(fn(string $operation) => $operation === 'edit'),

            Forms\Components\TextInput::make('selling_price')
                ->label('Harga Jual')
                ->numeric()
                ->prefix('Rp')
                ->required(fn(string $operation) => $operation === 'edit')
                ->visible(fn(string $operation) => $operation === 'edit'),

            Forms\Components\Select::make('unit')
                ->label('Satuan')
                ->options([
                    'biji' => 'Biji',
                    'lusin' => 'Lusin',
                    'pack' => 'Pack',
                    'dus' => 'Dus',
                    'rim' => 'Rim',
                    'pak' => 'Pak',
                    'box' => 'Box',
                    'rol' => 'Rol',
                    'set' => 'Set',
                ])
                ->required(fn(string $operation) => $operation === 'edit')
                ->visible(fn(string $operation) => $operation === 'edit'),

            Forms\Components\TextInput::make('variant')
                ->label('Varian Isi')
                ->placeholder('contoh: 50 lembar')
                ->maxLength(100)
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
