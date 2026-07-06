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
            <div class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto">
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeHistoryDetails"></div>
                <div class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl z-10">
                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start w-full">
                            <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left w-full">
                                <h3 class="text-base font-semibold leading-6 text-gray-900 border-b pb-2">
                                    {{ $selectedHistoryTitle }}
                                </h3>
                                <div class="mt-4 max-h-[300px] overflow-y-auto">
                                    <table class="w-full table-auto text-sm text-left">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-2">Nama Barang</th>
                                                <th class="px-4 py-2 text-right">Rekomendasi Qty</th>
                                                <th class="px-4 py-2 text-right">Estimasi Biaya</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($selectedHistoryDetails as $item)
                                                <tr class="border-t">
                                                    <td class="px-4 py-2 font-medium">{{ $item['product_name'] }}</td>
                                                    <td class="px-4 py-2 text-right">{{ number_format($item['qty'], 0, ',', '.') }} {{ $item['unit'] }}</td>
                                                    <td class="px-4 py-2 text-right">Rp {{ number_format($item['estimated_cost'], 0, ',', '.') }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <button type="button" class="mt-3 inline-flex w-full justify-center rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto" wire:click="closeHistoryDetails">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
