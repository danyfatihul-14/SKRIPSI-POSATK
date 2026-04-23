<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'User';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('store_id')
                ->label('Store')
                ->relationship('store', 'name_store')
                ->searchable()
                ->preload()
                ->nullable(),
            TextInput::make('name')
                ->required()
                ->maxLength(255),
            TextInput::make('username')
                ->required()
                ->maxLength(255)
                ->unique(ignoreRecord: true),
            TextInput::make('password')
                ->password()
                ->required(fn(string $context) => $context === 'create')
                ->minLength(6)
                ->dehydrated(fn($state) => filled($state)),
            Select::make('role')
                ->options([
                    'owner' => 'Owner',
                    'cashier' => 'Cashier',
                    'pelayan' => 'Pelayan',
                ])
                ->required(),
            Toggle::make('is_active')
                ->label('Active')
                ->default(true),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->searchable()->sortable(),
            TextColumn::make('username')->searchable()->sortable(),
            TextColumn::make('role')->badge(),
            TextColumn::make('store.name_store')->label('Store')->toggleable(),
            IconColumn::make('is_active')->boolean()->label('Active'),
            TextColumn::make('created_at')->dateTime()->toggleable(),
        ])->actions([
            \Filament\Tables\Actions\EditAction::make(),
            \Filament\Tables\Actions\DeleteAction::make(),
        ])->bulkActions([
            \Filament\Tables\Actions\DeleteBulkAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
