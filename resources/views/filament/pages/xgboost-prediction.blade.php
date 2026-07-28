<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-xl border bg-white p-4 shadow-sm">
            <div class="flex flex-col gap-4 md:flex-row md:items-end">
                <div class="w-full md:w-1/2">
                    <label class="mb-1 block text-sm font-medium text-gray-700">Toko</label>
                    <select wire:model="storeId" class="w-full rounded-lg border-gray-300 text-sm">
                        @foreach($stores as $store)
                            <option value="{{ $store['id'] }}">{{ $store['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="w-full md:w-1/4">
                    <label class="mb-1 block text-sm font-medium text-gray-700">Hari</label>
                    <input type="number" min="1" max="30" wire:model="days" class="w-full rounded-lg border-gray-300 text-sm" />
                </div>

                <div class="flex w-full gap-2 md:w-auto">
                    <x-filament::button color="gray" wire:click="checkMlService">
                        Cek Service
                    </x-filament::button>
                    <x-filament::button color="success" wire:click="runPrediction">
                        Jalankan Prediksi
                    </x-filament::button>
                </div>
            </div>
        </div>

        @if(!empty($runStatusMessage))
            <div class="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
                {{ $runStatusMessage }}
                <div class="mt-2 text-xs text-blue-700">
                    Total prediksi mentah: {{ number_format($runSummary['raw_predicted_units'] ?? 0, 0, ',', '.') }} unit | 
                    Rekomendasi kulak final: {{ number_format($runSummary['recommended_units'] ?? 0, 0, ',', '.') }} unit
                </div>
            </div>
        @endif

        <div class="rounded-xl border bg-white shadow-sm">
            <div class="border-b px-4 py-3">
                <h3 class="font-semibold">Rekomendasi Barang Kulak Per Tanggal</h3>
                <p class="mt-1 text-xs text-gray-500">Hasil ini adalah rekomendasi final setelah stok dan batas aman dihitung.</p>
            </div>

            <div class="p-4 space-y-4">
                @forelse($dailyRecommendations as $day)
                    <div class="rounded-lg border p-3">
                        <div class="flex items-center justify-between border-b pb-2">
                            <div class="text-sm font-semibold text-gray-900">{{ $day['date'] }}</div>
                            <div class="text-xs text-gray-600">
                                Total {{ number_format($day['total_qty'], 0, ',', '.') }} unit | Estimasi Rp {{ number_format($day['total_cost'], 0, ',', '.') }}
                            </div>
                        </div>
                        <div class="mt-2 overflow-x-auto">
                            <table class="w-full table-auto text-sm">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left">Barang</th>
                                        <th class="px-3 py-2 text-right">Qty</th>
                                        <th class="px-3 py-2 text-right">Estimasi Biaya</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($day['items'] as $item)
                                        <tr class="border-t">
                                            <td class="px-3 py-2">{{ $item['product_name'] }}</td>
                                            <td class="px-3 py-2 text-right">{{ number_format($item['qty'], 0, ',', '.') }} {{ $item['unit'] }}</td>
                                            <td class="px-3 py-2 text-right">Rp {{ number_format($item['estimated_cost'], 0, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @empty
                    <div class="text-sm text-gray-500">Belum ada hasil prediksi. Klik Jalankan Prediksi untuk menghasilkan daftar barang kulak.</div>
                @endforelse
            </div>
        </div>

        <div class="rounded-xl border bg-white shadow-sm">
            <div class="flex items-center justify-between border-b px-4 py-3">
                <h3 class="font-semibold">Riwayat Eksekusi Prediksi</h3>
                <x-filament::button size="sm" color="gray" wire:click="loadHistory">
                    Refresh
                </x-filament::button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full table-auto text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left">Tanggal Eksekusi</th>
                            <th class="px-4 py-3 text-left">Toko</th>
                            <th class="px-4 py-3 text-right">Jumlah Produk</th>
                            <th class="px-4 py-3 text-right">Total Prediksi (unit)</th>
                            <th class="px-4 py-3 text-right">Prediksi Revenue</th>
                            <th class="px-4 py-3 text-left">Start</th>
                            <th class="px-4 py-3 text-left">End</th>
                            <th class="px-4 py-3 text-right">Confidence</th>
                            <th class="px-4 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                            @forelse($history as $row)
                                <tr class="border-t hover:bg-gray-50">
                                    <td class="px-4 py-3">{{ $row['date'] }}</td>
                                    <td class="px-4 py-3">{{ $row['store'] }}</td>
                                    <td class="px-4 py-3 text-right">{{ number_format($row['product_count'] ?? 0, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-right">{{ number_format($row['predicted_quantity'] ?? 0, 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-right">Rp {{ number_format($row['revenue'], 0, ',', '.') }}</td>
                                    <td class="px-4 py-3 text-left">{{ $row['start_date'] ?? '-' }}</td>
                                    <td class="px-4 py-3 text-left">{{ $row['end_date'] ?? '-' }}</td>
                                    <td class="px-4 py-3 text-right">{{ number_format($row['confidence'] * 100, 2) }}%</td>
                                    <td class="px-4 py-3 text-center">
                                        <x-filament::button size="xs" color="info" wire:click="showHistoryDetails({{ $row['store_id'] }}, '{{ $row['date'] }}', '{{ $row['start_date'] ?? '-' }}', '{{ $row['end_date'] ?? '-' }}')">
                                            Lihat Detail
                                        </x-filament::button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-4 py-6 text-center text-gray-500">Belum ada riwayat prediksi.</td>
                                </tr>
                            @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($selectedHistoryDetails !== null)
            @php
                $totalQty = collect($selectedHistoryDetails)->sum('qty');
                $totalCost = collect($selectedHistoryDetails)->sum('estimated_cost');
            @endphp
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background-color: rgba(15, 23, 42, 0.5); backdrop-filter: blur(4px);">
                <div class="fixed inset-0" wire:click="closeHistoryDetails"></div>
                
                <div class="relative w-full max-w-3xl bg-white rounded-2xl shadow-2xl border border-gray-100 flex flex-col z-10 overflow-hidden transition-all duration-300" style="max-height: 90vh;">
                    
                    <!-- Header -->
                    <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 bg-gray-50">
                        <div class="flex items-center gap-2">
                            <span class="text-xl">📊</span>
                            <h3 class="text-lg font-bold text-gray-900 leading-none">
                                {{ $selectedHistoryTitle }}
                            </h3>
                        </div>
                        <button type="button" class="text-gray-400 hover:text-gray-600 transition-colors text-2xl font-semibold leading-none p-1" wire:click="closeHistoryDetails">
                            &times;
                        </button>
                    </div>
                    
                    <!-- Header Row (Static) -->
                    <div class="flex items-center justify-between bg-gray-50 border-b border-gray-100 px-6 py-2.5 text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <div class="flex-1" style="max-width: 60%;">Nama Barang</div>
                        <div class="flex items-center gap-6 text-right">
                            <div class="w-28">Rekomendasi Qty</div>
                            <div class="w-32">Estimasi Biaya</div>
                        </div>
                    </div>
                    
                    <!-- Body / List (Scrollable) -->
                    <div class="flex-1 overflow-y-auto px-6 divide-y divide-gray-100" style="max-height: 50vh; min-height: 250px;">
                        @foreach($selectedHistoryDetails as $item)
                            <div class="flex items-center justify-between py-2 hover:bg-gray-50/80 transition-colors text-sm">
                                <div class="flex-1 font-medium text-gray-800 pr-4 truncate" style="max-width: 60%;" title="{{ $item['product_name'] }}">
                                    {{ $item['product_name'] }}
                                </div>
                                <div class="flex items-center gap-6 text-right text-gray-600">
                                    <div class="w-28 font-semibold">{{ number_format($item['qty'], 0, ',', '.') }} {{ $item['unit'] }}</div>
                                    <div class="w-32 font-medium text-gray-900">Rp {{ number_format($item['estimated_cost'], 0, ',', '.') }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    
                    <!-- Footer / Summary -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-t border-gray-100 px-6 py-4 bg-gray-50">
                        <div class="text-sm text-gray-700 font-medium">
                            Ringkasan: <span class="text-blue-600 font-bold">{{ number_format($totalQty, 0, ',', '.') }} unit</span> | 
                            Total Biaya: <span class="text-green-600 font-bold">Rp {{ number_format($totalCost, 0, ',', '.') }}</span>
                        </div>
                        <button type="button" class="inline-flex justify-center rounded-xl bg-white border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 transition-colors" wire:click="closeHistoryDetails">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
