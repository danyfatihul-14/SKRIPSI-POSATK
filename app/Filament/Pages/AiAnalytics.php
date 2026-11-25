<?php

namespace App\Filament\Pages;

use App\Models\Store;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class AiAnalytics extends Page
{
    protected static ?string $navigationGroup = null;
    protected static ?string $navigationLabel = 'AI Analitik Bisnis';
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    protected static ?int $navigationSort = 6;
    protected static string $view = 'filament.pages.ai-analytics';

    public function getViewData(): array
    {
        $stores = Store::query()
            ->orderBy('name_store')
            ->get(['store_id', 'name_store'])
            ->map(fn (Store $store): array => [
                'id' => (int) $store->store_id,
                'name' => (string) $store->name_store,
            ])
            ->all();

        $defaultStoreId = Auth::user()?->store_id ?? ($stores[0]['id'] ?? 1);

        return [
            'stores'         => $stores,
            'defaultStoreId' => $defaultStoreId,
        ];
    }
}
