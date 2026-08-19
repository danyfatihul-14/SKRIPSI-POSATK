<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Closing Kasir - {{ $cashier->name }} ({{ $date }})</title>
    <style>
        @page { margin: 0; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Courier New', monospace;
            background: #fff;
            padding: 8px 10px;
            width: 80mm;
            max-width: 100%;
            margin: 0 auto;
            font-size: 12px;
            line-height: 1.2;
            color: #000;
        }
        .header { text-align: center; border-bottom: 1px dashed #000; padding-bottom: 6px; margin-bottom: 8px; }
        .store-name { font-weight: bold; font-size: 14px; }
        .title { font-weight: bold; font-size: 13px; margin: 4px 0; text-transform: uppercase; }
        .divider { border-bottom: 1px dashed #000; margin: 6px 0; }
        .row { display: flex; justify-content: space-between; margin-bottom: 3px; }
        .row-bold { font-weight: bold; font-size: 13px; border-top: 1px solid #000; border-bottom: 1px solid #000; padding: 4px 0; margin: 6px 0; }
        .footer { text-align: center; margin-top: 10px; font-size: 10px; color: #333; }
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="store-name">TOKO KURNIA CABANG{{ $store?->name_store ? ' - ' . $store->name_store : '' }}</div>
        <div class="title">*** LAPORAN CLOSING KASIR ***</div>
        <div>Tanggal: {{ $formatted_date }}</div>
        <div>Kasir: {{ $cashier->name }}</div>
        <div>Dicetak: {{ now('Asia/Jakarta')->format('d/m/Y H:i:s') }}</div>
    </div>

    <div class="divider"></div>

    <div class="row">
        <span>Total Transaksi</span>
        <span>{{ number_format($totalCount, 0, ',', '.') }} Trx</span>
    </div>
    <div class="row">
        <span>Pembayaran Tunai (Cash)</span>
        <span>Rp{{ number_format($totalCash, 0, ',', '.') }}</span>
    </div>
    <div class="row">
        <span>Pembayaran QRIS/E-Wallet</span>
        <span>Rp{{ number_format($totalQris, 0, ',', '.') }}</span>
    </div>

    <div class="row row-bold">
        <span>TOTAL OMSET</span>
        <span>Rp{{ number_format($totalAmount, 0, ',', '.') }}</span>
    </div>

    <div class="divider"></div>
    <div style="font-weight: bold; margin-bottom: 4px;">RINGKASAN TRANSAKSI:</div>

    @forelse($transactions as $trx)
    <div class="row" style="font-size: 11px;">
        <span>{{ $trx->created_at?->format('H:i') }} #{{ $trx->transaction_code }} ({{ strtoupper($trx->payment_method ?? 'CASH') }})</span>
        <span>Rp{{ number_format($trx->total_amount, 0, ',', '.') }}</span>
    </div>
    @empty
    <div style="text-align: center; color: #666;">Belum ada transaksi selesai.</div>
    @endforelse

    <div class="divider"></div>

    <div class="no-print" style="margin-top: 15px; text-align: center;">
        <button onclick="window.print()" style="padding: 8px 16px; background: #2563eb; color: #fff; border: none; border-radius: 6px; font-weight: bold; cursor: pointer;">🖨️ Cetak Struk Closing</button>
    </div>

    <script>
        window.onload = function() {
            // Auto trigger print if parameter print=1 is passed
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('print') === '1') {
                window.print();
            }
        };
    </script>
</body>
</html>
