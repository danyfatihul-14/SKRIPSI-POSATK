<?php
// filepath: app/Filament/Cashier/Pages/CashierDashboard.php

namespace App\Filament\Cashier\Pages;

use Filament\Pages\Page;

class CashierDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationLabel = 'Kasir';
    protected static ?string $title = 'Kasir';
    protected static string $view = 'filament.cashier.pages.cashier-dashboard';

    // Jangan override layout, biarkan Filament handle-nya
}
