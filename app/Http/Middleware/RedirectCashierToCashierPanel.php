<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectCashierToCashierPanel
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->role === 'cashier') {
            $panel = Filament::getPanel('cashier');
            $target = $panel?->getUrl() ?? url('/cashier');

            return redirect()->to($target);
        }

        return $next($request);
    }
}
