<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Filament\Support\Facades\FilamentIcon;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use App\Http\Responses\LoginResponse;
use Filament\Http\Responses\Auth\Contracts\LogoutResponse as LogoutResponseContract;
use App\Http\Responses\LogoutResponse;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(LoginResponseContract::class, LoginResponse::class);
        $this->app->bind(LogoutResponseContract::class, LogoutResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        FilamentIcon::register([
            'panels::sidebar.collapse-button' => 'heroicon-m-bars-3',
            'panels::sidebar.collapse-button.rtl' => 'heroicon-m-bars-3',
            'panels::sidebar.expand-button' => 'heroicon-m-bars-3',
            'panels::sidebar.expand-button.rtl' => 'heroicon-m-bars-3',
        ]);
    }
}
