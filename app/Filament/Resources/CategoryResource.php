<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoryResource\Pages;
use App\Models\Category;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'Category';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('category_name')
                ->label('Category Name')
                ->required()
                ->maxLength(100)
                ->placeholder('Pensil'),

            Textarea::make('description')
                ->label('Description')
                ->maxLength(65535)
                ->rows(3)
                ->columnSpanFull()
                ->placeholder('Optional description'),

            Toggle::make('is_dynamic_price')
                ->label('Harga Dinamis (Kasir)')
                ->helperText('Aktifkan jika produk di kategori ini meminta harga jual dinamis saat checkout di kasir.')
                ->reactive()
                ->default(false),

            TextInput::make('profit_margin')
                ->label('Margin Profit (%)')
                ->helperText('Persentase keuntungan dari harga jual (misal: 25 untuk 25%).')
                ->numeric()
                ->default(0.0)
                ->suffix('%')
                ->visible(fn ($get) => $get('is_dynamic_price'))
                ->required(fn ($get) => $get('is_dynamic_price')),

            TagsInput::make('keywords')
                ->label('Kata Kunci Pendeteksi Tambahan')
                ->helperText('Masukkan kata kunci pencarian tambahan (misal: gelang, cincin) agar produk non-kategori ini juga terdeteksi sebagai harga dinamis.')
                ->placeholder('Tambah kata kunci...')
                ->separator(',')
                ->visible(fn ($get) => $get('is_dynamic_price'))
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('category_name')
                    ->label('Category')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('description')
                    ->label('Description')
                    ->limit(60)
                    ->toggleable()
                    ->searchable(),

                IconColumn::make('is_dynamic_price')
                    ->label('Harga Dinamis')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('profit_margin')
                    ->label('Margin Profit')
                    ->suffix('%')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
