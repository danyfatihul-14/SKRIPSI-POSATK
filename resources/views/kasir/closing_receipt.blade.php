<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk Closing & Produk Keluar - {{ $store?->name_store ?? 'Toko' }} ({{ $date }})</title>
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
        @media print {
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="store-name">TOKO KURNIA CABANG{{ $store?->name_store ? ' - ' . $store->name_store : '' }}</div>
        <div class="title">*** LAPORAN PRODUK KELUAR (CLOSING) ***</div>
        <div>Tanggal: {{ $formatted_date }}</div>
        <div>Dicetak Oleh: {{ $cashier->name }}</div>
        <div>Waktu Cetak: {{ now('Asia/Jakarta')->format('d/m/Y H:i:s') }}</div>
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
    <div style="font-weight: bold; margin-bottom: 6px; text-transform: uppercase;">
        📦 RINCIAN PRODUK KELUAR (REKAP KULAKAN):
    </div>

    @forelse($productsSummary as $p)
    @if(($p['qty'] ?? 0) >= 1)
    <div style="margin-bottom: 4px; font-size: 11px;">
        <div style="font-weight: bold;">{{ $p['name'] }}</div>
        <div class="row">
            <span>  Total Keluar: {{ $p['qty'] }} pcs</span>
            <span>Rp{{ number_format($p['subtotal'], 0, ',', '.') }}</span>
        </div>
    </div>
    @endif
    @empty
    <div style="text-align: center; color: #666;">Belum ada produk terjual.</div>
    @endforelse

    <div class="divider"></div>
    <div class="row" style="font-weight: bold; font-size: 12px; margin-top: 4px;">
        <span>TOTAL ITEM KELUAR</span>
        <span>{{ number_format($totalQtySold, 0, ',', '.') }} Pcs</span>
    </div>

    <div class="divider"></div>

    <div class="no-print" style="margin-top: 15px; text-align: center;">
        <button onclick="window.print()" style="padding: 8px 16px; background: #2563eb; color: #fff; border: none; border-radius: 6px; font-weight: bold; cursor: pointer;">🖨️ Cetak Struk Rekap Kulakan</button>
    </div>

    <script>
        window.onload = function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('print') === '1') {
                window.print();
            }
        };
    </script>
</body>
</html>
