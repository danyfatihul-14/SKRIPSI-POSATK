<?php

namespace App\Http\Responses;

use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): RedirectResponse | Redirector
    {
        $user = Filament::auth()->user();

        if (! $user) {
            return redirect()->to(Filament::getUrl());
        }

        if ($user->role === 'cashier') {
            return redirect()->to('/cashier');
        }

        if ($user->role === 'owner') {
            return redirect()->to('/admin');
        }

        return redirect()->to(Filament::getUrl());
    }
}
