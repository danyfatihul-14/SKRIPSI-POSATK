<?php

namespace App\Http\Responses;

use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as Responsable;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class CashierLoginResponse implements Responsable
{
    public function toResponse($request): RedirectResponse|Redirector
    {
        $user = Filament::auth()->user();

        if (! $user) {
            return redirect()->intended(Filament::getUrl());
        }

        $panelId = $user->role === 'cashier' ? 'cashier' : 'admin';
        $panel = Filament::getPanel($panelId);

        return redirect()->to($panel?->getUrl() ?? Filament::getUrl());
    }
}
