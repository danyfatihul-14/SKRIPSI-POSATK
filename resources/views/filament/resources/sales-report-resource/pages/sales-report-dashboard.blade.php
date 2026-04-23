<x-filament-panels::page>
    <div class="space-y-6">
        <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
            <div class="rounded-xl border bg-white p-4 shadow-sm" wire:ignore>
                <h3 class="font-semibold mb-3">Trend Omzet 30 Hari</h3>
                <canvas id="omzetTrendChart" height="120"></canvas>
            </div>

            <div class="rounded-xl border bg-white p-4 shadow-sm" wire:ignore>
                <h3 class="font-semibold mb-3">Trend Transaksi 30 Hari</h3>
                <canvas id="trxTrendChart" height="120"></canvas>
            </div>
        </div>
        <div class="rounded-xl border bg-white shadow-sm">
            <div class="px-4 py-3 border-b font-semibold text-lg">Data Penjualan</div>
            <div class="overflow-x-auto">
                <table class="w-full table-auto text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left">Periode</th>
                            <th class="px-4 py-3 text-right">Jumlah Transaksi</th>
                            <th class="px-4 py-3 text-right">Total Penjualan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($summaryRows as $row)
                        <tr class="border-t hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium">{{ $row['periode'] }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($row['trx'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right font-semibold">Rp {{ number_format($row['uang'], 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="px-4 py-6 text-center text-gray-500">Belum ada data</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-xl border bg-white shadow-sm">
            <div class="px-4 py-3 border-b font-semibold text-lg">Riwayat Transaksi</div>
            <div class="overflow-x-auto">
                <table class="w-full table-auto text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left">Kode</th>
                            <th class="px-4 py-3 text-left">Waktu</th>
                            <th class="px-4 py-3 text-left">Melayani</th>
                            <th class="px-4 py-3 text-left">Metode</th>
                            <th class="px-4 py-3 text-right">Total</th>
                            <th class="px-4 py-3 text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentTransactions as $trx)
                        <tr class="border-t hover:bg-gray-50">
                            <td class="px-4 py-3">{{ $trx['code'] }}</td>
                            <td class="px-4 py-3">{{ $trx['time'] }}</td>
                            <td class="px-4 py-3">{{ $trx['cashier'] }}</td>
                            <td class="px-4 py-3">{{ $trx['method'] }}</td>
                            <td class="px-4 py-3 text-right">Rp {{ number_format($trx['total'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-center">
                                <button
                                    wire:click="showTransactionDetail('{{ $trx['id'] }}')"
                                    class="px-3 py-1.5 rounded-lg bg-primary-600 text-white text-xs font-medium hover:bg-primary-700">
                                    Detail
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-gray-500">Belum ada transaksi</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if($selectedTransaction)
        <div class="rounded-xl border bg-white shadow-sm">
            <div class="px-4 py-3 border-b">
                <div class="font-semibold text-lg">Detail Transaksi: {{ $selectedTransaction['code'] }}</div>
                <div class="text-sm text-gray-500">
                    Waktu: {{ $selectedTransaction['time'] }} • Melayani: {{ $selectedTransaction['cashier'] }}
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full table-auto text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left">Produk</th>
                            <th class="px-4 py-3 text-right">Qty</th>
                            <th class="px-4 py-3 text-right">Harga</th>
                            <th class="px-4 py-3 text-right">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($selectedTransactionItems as $item)
                        <tr class="border-t">
                            <td class="px-4 py-3">{{ $item['product_name'] }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($item['qty'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right">Rp {{ number_format($item['price'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right font-semibold">Rp {{ number_format($item['subtotal'], 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-gray-500">Item transaksi tidak ditemukan</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    @once
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @endonce

    @php
    $chartData = [
    'labels' => $trendLabels ?? [],
    'sales' => $trendSales ?? [],
    'trx' => $trendTrx ?? [],
    ];
    @endphp

    <script>
        window.__salesChartData = @json($chartData);
    </script>

    <script>
        (() => {
            let omzetChart = null;
            let trxChart = null;

            function getChartData() {
                return window.__salesChartData ?? {
                    labels: [],
                    sales: [],
                    trx: []
                };
            }

            function renderCharts() {
                const omzetEl = document.getElementById('omzetTrendChart');
                const trxEl = document.getElementById('trxTrendChart');
                if (!omzetEl || !trxEl || typeof Chart === 'undefined') return;

                const {
                    labels,
                    sales,
                    trx
                } = getChartData();

                if (omzetChart) omzetChart.destroy();
                if (trxChart) trxChart.destroy();

                omzetChart = new Chart(omzetEl, {
                    type: 'line',
                    data: {
                        labels,
                        datasets: [{
                            label: 'Omzet',
                            data: sales,
                            borderColor: '#2563eb',
                            backgroundColor: 'rgba(37,99,235,.12)',
                            fill: true,
                            tension: 0.35
                        }]
                    },
                    options: {
                        responsive: true
                    }
                });

                trxChart = new Chart(trxEl, {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [{
                            label: 'Transaksi',
                            data: trx,
                            backgroundColor: '#f59e0b'
                        }]
                    },
                    options: {
                        responsive: true
                    }
                });
            }

            document.addEventListener('DOMContentLoaded', renderCharts);
            document.addEventListener('livewire:navigated', renderCharts);
        })();
    </script>
</x-filament-panels::page>