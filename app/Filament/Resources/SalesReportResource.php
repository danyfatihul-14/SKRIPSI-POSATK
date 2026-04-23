<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SalesReportResource\Pages\SalesReportDashboard;
use App\Models\Transaction;
use Filament\Resources\Resource;

class SalesReportResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationGroup = 'Laporan';
    protected static ?string $navigationLabel = 'Laporan Penjualan';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    public static function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form;
    }

    public static function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return $table;
    }

    public static function getPages(): array
    {
        return [
            'index' => SalesReportDashboard::route('/'),
        ];
    }
}
