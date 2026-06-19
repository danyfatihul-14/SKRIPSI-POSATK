<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Filters --}}
        <div class="flex gap-4 items-center flex-wrap">
            <div class="w-64">
                <x-filament::input.wrapper>
                    <x-filament::input.select wire:model.live="storeId">
                        <option value="">Semua Toko</option>
                        @foreach($stores as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </div>
            
            <x-filament::button wire:click="$refresh" color="gray" icon="heroicon-m-arrow-path" size="sm">
                Refresh
            </x-filament::button>
        </div>

        {{-- Grid for Low Stock and Logs --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            {{-- Column 1: Low Stock Alert --}}
            <div class="lg:col-span-1 space-y-4">
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 shadow-sm">
                    <div class="flex items-center justify-between border-b border-gray-100 dark:border-gray-800 pb-3 mb-4">
                        <div class="flex items-center gap-2">
                            <span class="flex h-3 w-3 relative">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                            </span>
                            <h3 class="font-bold text-gray-900 dark:text-white text-base">Peringatan Stok Habis</h3>
                        </div>
                        <span class="bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 text-xs font-semibold px-2.5 py-1 rounded-full">
                            {{ count($this->getLowStockProducts()) }} Item
                        </span>
                    </div>

                    <div class="space-y-3 max-h-[600px] overflow-y-auto pr-1">
                        @forelse($this->getLowStockProducts() as $item)
                            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg border border-gray-100 dark:border-gray-800">
                                <div class="min-w-0">
                                    <h4 class="font-bold text-xs text-gray-900 dark:text-white truncate">{{ $item->product?->product_name }}</h4>
                                    <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-1">
                                        Varian: {{ $item->product?->variant ?: '-' }} | Store: {{ $item->store?->name_store }}
                                    </p>
                                </div>
                                <div class="text-right ml-4">
                                    <span class="inline-block text-xs font-bold px-2 py-0.5 rounded-full {{ $item->quantity == 0 ? 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-400' : 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-400' }}">
                                        {{ $item->quantity == 0 ? 'Habis' : $item->quantity . ' ' . $item->product?->unit }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-6 text-gray-500 dark:text-gray-400 text-xs">
                                Semua stok aman (tidak ada stok di bawah atau sama dengan 5).
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Column 2 & 3: Stock Activity Log --}}
            <div class="lg:col-span-2 space-y-4">
                <div class="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-xl p-4 shadow-sm">
                    <div class="border-b border-gray-100 dark:border-gray-800 pb-3 mb-4">
                        <h3 class="font-bold text-gray-900 dark:text-white text-base">Riwayat Perubahan Stok (Aktivitas Kasir & Pelayan)</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Mencatat seluruh penyesuaian stok, konversi satuan, dan penjualan.</p>
                    </div>

                    @php
                        $logs = $this->getStockLogs();
                    @endphp

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="bg-gray-50 dark:bg-gray-800/50 text-gray-600 dark:text-gray-300 font-bold uppercase tracking-wider border-b border-gray-100 dark:border-gray-800">
                                    <th class="p-3">Waktu</th>
                                    <th class="p-3">User / Store</th>
                                    <th class="p-3">Produk</th>
                                    <th class="p-3">Tipe</th>
                                    <th class="p-3 text-center">Stok Awal</th>
                                    <th class="p-3 text-center">Perubahan</th>
                                    <th class="p-3 text-center">Stok Akhir</th>
                                    <th class="p-3">Catatan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800 text-gray-800 dark:text-gray-200">
                                @forelse($logs as $log)
                                    <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30">
                                        <td class="p-3 whitespace-nowrap text-gray-500 dark:text-gray-400">
                                            {{ $log->created_at->format('d/m/Y H:i:s') }}
                                        </td>
                                        <td class="p-3">
                                            <div class="font-semibold">{{ $log->user?->name ?? 'System' }}</div>
                                            <div class="text-[10px] text-gray-500 dark:text-gray-400">{{ $log->store?->name_store }}</div>
                                        </td>
                                        <td class="p-3">
                                            <div class="font-semibold">{{ $log->product?->product_name }}</div>
                                            <div class="text-[10px] text-gray-500 dark:text-gray-400">Varian: {{ $log->product?->variant ?: '-' }}</div>
                                        </td>
                                        <td class="p-3 whitespace-nowrap">
                                            @php
                                                $badgeClass = match($log->type) {
                                                    'adjustment' => 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-400',
                                                    'conversion_out' => 'bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-400',
                                                    'conversion_in' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-400',
                                                    'sale' => 'bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-400',
                                                    default => 'bg-gray-100 text-gray-800 dark:bg-gray-950 dark:text-gray-400'
                                                };
                                                $typeLabel = match($log->type) {
                                                    'adjustment' => 'Penyesuaian',
                                                    'conversion_out' => 'Pecah Stok (-)',
                                                    'conversion_in' => 'Konversi (+)',
                                                    'sale' => 'Penjualan',
                                                    default => $log->type
                                                };
                                            @endphp
                                            <span class="inline-block text-[10px] font-bold px-2 py-0.5 rounded-full {{ $badgeClass }}">
                                                {{ $typeLabel }}
                                            </span>
                                        </td>
                                        <td class="p-3 text-center text-gray-500 dark:text-gray-400">
                                            {{ $log->old_quantity }}
                                        </td>
                                        <td class="p-3 text-center font-bold">
                                            <span class="{{ $log->quantity_change > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                                {{ $log->quantity_change > 0 ? '+' : '' }}{{ $log->quantity_change }}
                                            </span>
                                        </td>
                                        <td class="p-3 text-center font-bold">
                                            {{ $log->new_quantity }}
                                        </td>
                                        <td class="p-3 text-gray-500 dark:text-gray-400 max-w-[200px] truncate" title="{{ $log->notes }}">
                                            {{ $log->notes ?: '-' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-8 text-gray-500 dark:text-gray-400">
                                            Belum ada riwayat aktivitas perubahan stok.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    @if($logs instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator && $logs->hasPages())
                        <div class="mt-4 pt-3 border-t border-gray-100 dark:border-gray-800">
                            {{ $logs->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
