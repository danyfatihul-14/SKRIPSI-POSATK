<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StoreResource\Pages;
use App\Models\Store;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StoreResource extends Resource
{
    protected static ?string $model = Store::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationLabel = 'Store';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('code_store')
                ->label('Store Code')
                ->required()
                ->maxLength(20)
                ->unique(ignoreRecord: true)
                ->placeholder('STR-001'),

            TextInput::make('name_store')
                ->label('Store Name')
                ->required()
                ->maxLength(255)
                ->placeholder('Main Store'),

            Textarea::make('address')
                ->label('Address')
                ->maxLength(65535)
                ->rows(3)
                ->columnSpanFull()
                ->placeholder('Enter store address'),

            TextInput::make('phone')
                ->label('Phone Number')
                ->tel()
                ->maxLength(20)
                ->placeholder('081234567890'),

            TextInput::make('manager_name')
                ->label('Manager Name')
                ->maxLength(100)
                ->placeholder('John Doe'),

            Toggle::make('is_active')
                ->label('Active')
                ->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code_store')
                    ->label('Code')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Code copied!'),

                TextColumn::make('name_store')
                    ->label('Store Name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('address')
                    ->label('Address')
                    ->limit(50)
                    ->toggleable()
                    ->searchable(),

                TextColumn::make('phone')
                    ->label('Phone')
                    ->toggleable()
                    ->searchable(),

                TextColumn::make('manager_name')
                    ->label('Manager')
                    ->toggleable()
                    ->searchable(),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('users_count')
                    ->label('Users')
                    ->counts('users')
                    ->badge()
                    ->toggleable(),

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
            ->filters([
                //
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
            'index' => Pages\ListStores::route('/'),
            'create' => Pages\CreateStore::route('/create'),
            'edit' => Pages\EditStore::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
