<x-filament-panels::page>
    <div class="space-y-6">
        <div class="rounded-xl border bg-white p-4 shadow-sm" wire:ignore>
            <h3 class="font-semibold mb-3">Top Produk Bulanan</h3>
            <canvas id="topProductChart" height="120"></canvas>
        </div>

        <div class="rounded-xl border bg-white shadow-sm">
            <div class="px-4 py-3 border-b font-semibold text-lg">Produk Terbanyak</div>
            <div class="overflow-x-auto">
                <table class="w-full table-auto text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left">Periode</th>
                            <th class="px-4 py-3 text-left">Produk</th>
                            <th class="px-4 py-3 text-right">Qty Terjual</th>
                            <th class="px-4 py-3 text-right">Omzet</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topRows as $row)
                        <tr class="border-t hover:bg-gray-50">
                            <td class="px-4 py-3">{{ $row['periode'] }}</td>
                            <td class="px-4 py-3">{{ $row['product_name'] }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format($row['qty'], 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right">Rp {{ number_format($row['omzet'], 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-gray-500">Belum ada data</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @once
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @endonce

    @php
    $productChartData = [
    'labels' => $topProductLabels ?? [],
    'qty' => $topProductQty ?? [],
    ];
    @endphp

    <script>
        window.__productChartData = @json($productChartData);
    </script>

    <script>
        (() => {
            let topChart = null;

            function getChartData() {
                return window.__productChartData ?? {
                    labels: [],
                    qty: []
                };
            }

            function renderChart() {
                const el = document.getElementById('topProductChart');
                if (!el || typeof Chart === 'undefined') return;

                const {
                    labels,
                    qty
                } = getChartData();

                if (topChart) topChart.destroy();

                topChart = new Chart(el, {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [{
                            label: 'Qty Terjual',
                            data: qty,
                            backgroundColor: '#10b981'
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
            }

            document.addEventListener('DOMContentLoaded', renderChart);
            document.addEventListener('livewire:navigated', renderChart);
        })();
    </script>
</x-filament-panels::page>