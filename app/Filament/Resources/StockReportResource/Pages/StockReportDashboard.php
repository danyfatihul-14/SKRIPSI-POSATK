<?php
// filepath: app/Filament/Resources/StockReportResource/Pages/StockReportDashboard.php

namespace App\Filament\Resources\StockReportResource\Pages;

use App\Filament\Resources\StockReportResource;
use Filament\Resources\Pages\Page;
use App\Models\StockLevel;
use App\Models\StockLog;
use App\Models\Store;
use Livewire\WithPagination;

class StockReportDashboard extends Page
{
    use WithPagination;

    protected static string $resource = StockReportResource::class;
    protected static string $view = 'filament.resources.stock-report-resource.pages.stock-report-dashboard';
    protected static ?string $title = 'Laporan Stok';

    public ?int $storeId = null;
    public array $stores = [];
    public int $perPage = 15;
    public int $lowStockPerPage = 10;

    public function mount(): void
    {
        $this->stores = Store::query()->pluck('name_store', 'store_id')->toArray();
        $userStoreId = auth()->user()?->store_id;
        if ($userStoreId) {
            $this->storeId = (int)$userStoreId;
        } else {
            $this->storeId = null;
        }
    }

    public function updatedStoreId(): void
    {
        $this->resetPage();
        $this->resetPage('lowStockPage');
    }

    public function updatedLowStockPerPage(): void
    {
        $this->resetPage('lowStockPage');
    }

    public function getLowStockProducts()
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('stock_levels')) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $this->lowStockPerPage);
        }

        $query = StockLevel::query()
            ->with(['product', 'store'])
            ->where('quantity', '<=', 5);

        if ($this->storeId) {
            $query->where('store_id', $this->storeId);
        }

        return $query->orderBy('quantity', 'asc')->paginate($this->lowStockPerPage, ['*'], 'lowStockPage');
    }

    public function getStockLogs()
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('stock_logs')) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $this->perPage);
        }

        $query = StockLog::query()
            ->with(['product', 'store', 'user'])
            ->latest('created_at');

        if ($this->storeId) {
            $query->where('store_id', $this->storeId);
        }

        return $query->paginate($this->perPage);
    }
}
