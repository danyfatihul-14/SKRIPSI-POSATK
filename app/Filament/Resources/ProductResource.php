<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use App\Models\Store;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Forms\Components\FileUpload;

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
                ->disk('public')
                ->directory('products')
                ->maxSize(2048)
                ->maxFiles(1),

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

            // Field stok awal (untuk create/edit)
            Forms\Components\Select::make('initial_store_id')
                ->label('Toko (Stok Awal)')
                ->options(fn() => Store::query()->pluck('name_store', 'store_id'))
                ->searchable()
                ->required()
                ->dehydrated(false), // jangan masuk ke table products

            Forms\Components\TextInput::make('initial_stock')
                ->label('Stok Awal')
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->required()
                ->dehydrated(false),

            Forms\Components\TextInput::make('initial_discount')
                ->label('Diskon Awal')
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->dehydrated(false),
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
