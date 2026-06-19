<?php
// filepath: app/Filament/Resources/StockReportResource.php

namespace App\Filament\Resources;

use App\Filament\Resources\StockReportResource\Pages\StockReportDashboard;
use App\Models\StockLog;
use Filament\Resources\Resource;

class StockReportResource extends Resource
{
    protected static ?string $model = StockLog::class;

    protected static ?string $navigationGroup = 'Laporan';
    protected static ?string $navigationLabel = 'Laporan Stok';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

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
            'index' => StockReportDashboard::route('/'),
        ];
    }
}
