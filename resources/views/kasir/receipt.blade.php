<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk {{ $transaction->transaction_code }}</title>
    <style>
        @page {
            /* Hapus size: 80mm 210mm; agar tinggi kertas tidak dipaksa 21cm */
            margin: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', monospace;
            background: #f5f5f5;
            padding: 12px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            /* min-height: 100vh dihapus agar tinggi body mengikuti konten */
        }

        .receipt-wrapper {
            background: #fff;
            width: 80mm;
            box-sizing: border-box;
            margin: 0 auto;
        }

        /* Thermal Printer Style - 80mm width */
        .thermal-receipt {
            width: 80mm;
            padding: 5px 6px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            line-height: 1.1;
            background: #fff;
            color: #000;
            box-sizing: border-box;
        }

        .thermal-header {
            text-align: center;
            margin-bottom: 8px;
            border-bottom: 1px dashed #000;
            padding-bottom: 6px;
        }

        .thermal-divider {
            border-bottom: 1px dashed #000;
            margin: 6px 0;
        }

        .thermal-store-name {
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 2px;
        }

        .thermal-store-info {
            font-size: 11px;
            color: #333;
            margin-bottom: 8px;
        }

        .thermal-code {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 4px;
            letter-spacing: 1px;
        }

        .thermal-datetime {
            font-size: 11px;
            margin-bottom: 10px;
        }

        .thermal-content {
            margin-bottom: 10px;
        }

        .thermal-item {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            margin-bottom: 3px;
            word-wrap: break-word;
            line-height: 1.15;
        }

        .thermal-item-name {
            display: block;
            margin-bottom: 2px;
        }

        .thermal-item-detail {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            padding: 0;
        }

        .thermal-item-qty-price {
            flex: 1;
            text-align: left;
        }

        .thermal-item-price-right {
            text-align: right;
            min-width: 60px;
        }

        .thermal-summary {
            font-size: 12px;
            margin-bottom: 8px;
        }

        .thermal-summary-row {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            margin-bottom: 2px;
            letter-spacing: 0.5px;
        }

        .thermal-summary-row span:first-child {
            flex: 1;
        }

        .thermal-summary-row span:last-child {
            text-align: right;
            min-width: 70px;
        }

        .thermal-total {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 4px 0;
            margin: 6px 0;
            font-weight: bold;
            font-size: 13px;
            display: flex;
            justify-content: space-between;
        }

        .thermal-total span:last-child {
            text-align: right;
            min-width: 70px;
        }

        .thermal-footer {
            text-align: center;
            font-size: 11px;
            color: #666;
            margin-top: 10px;
            padding-top: 8px;
            border-top: 1px dashed #000;
        }

        .thermal-footer-text {
            margin-bottom: 4px;
        }

        .thermal-thank-you {
            font-weight: bold;
            margin: 8px 0;
        }

        .thermal-cashier {
            font-size: 10px;
            margin-top: 8px;
            color: #666;
        }

        .thermal-jagged {
            position: relative;
            margin-top: 12px;
            height: 8px;
        }

        .thermal-jagged::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 8px;
            background: radial-gradient(3px at 3px 0, #fff 3px, transparent 3px);
            background-size: 6px 8px;
            background-repeat: repeat-x;
            background-color: #fff;
        }

        /* PRINT STYLES - Diperbarui agar tinggi dinamis */
        @media print {
            @page {
                margin: 0;
                size: auto;
                /* Biarkan pengaturan Roll Paper Windows yang bekerja */
            }

            html,
            body {
                background: #fff;
                padding: 0;
                margin: 0;
                width: 100%;
                height: auto !important;
            }

            .receipt-wrapper {
                width: 100%;
                /* Mengikuti lebar asli printable area (72.1mm) */
                box-shadow: none;
                margin: 0;
                padding: 0;
            }

            .thermal-receipt {
                width: 100%;
                padding: 2mm 5mm !important;
                /* Memberikan jarak aman kiri-kanan */
                font-size: 12px;
                box-sizing: border-box;
                height: auto !important;
            }

            .no-print {
                display: none !important;
            }

            .thermal-jagged {
                display: none;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 10px;
            }

            .thermal-receipt {
                font-size: 12px;
            }
        }

        /* Button styles */
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            padding: 0 12px;
            justify-content: center;
        }

        .btn {
            padding: 10px 16px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all .2s;
        }

        .btn-download {
            background: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
        }

        .btn-download:hover {
            background: #e5e7eb;
        }

        .btn-print {
            background: #1f2937;
            color: #fff;
        }

        .btn-print:hover {
            background: #111827;
        }

        .btn-back {
            background: #e5e7eb;
            color: #374151;
            flex: 1;
            justify-content: center;
            text-decoration: none;
            /* Tambahan agar link terlihat seperti tombol */
        }

        .btn-back:hover {
            background: #d1d5db;
        }
    </style>
</head>

<body>
    <div class="receipt-wrapper">
        <div class="thermal-receipt">
            <!-- Header -->
            <div class="thermal-header">
                <div class="thermal-store-name">TOKO INTAN</div>
                <div class="thermal-store-info">
                    Jl. Raya Sabar<br>
                    Kota Blitar<br>
                    031-012345
                </div>
            </div>

            <!-- Transaction Info -->
            <div class="thermal-code">{{ $transaction->transaction_code }}</div>
            <div class="thermal-datetime">
                {{ optional($transaction->created_at)->format('d/m/Y H:i') }}<br>
                Kasir: {{ $transaction->user?->name ?? '-' }}<br>
                Pembeli: {{ $transaction->customer_name ?? 'Pelanggan Umum' }}
            </div>

            <!-- Items -->
            <div class="thermal-divider"></div>
            <div class="thermal-content">
                @foreach($transaction->items as $item)
                <div class="thermal-item">
                    <div class="thermal-item-name">
                        {{ substr($item->product?->product_name ?? 'Produk', 0, 19) }}
                    </div>
                    <div class="thermal-item-detail">
                        <div class="thermal-item-qty-price">
                            {{ (int)$item->quantity }} x {{ number_format($item->unit_price, 0, ',', '.') }}
                        </div>
                        <div class="thermal-item-price-right">
                            {{ number_format($item->subtotal, 0, ',', '.') }}
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Summary -->
            <div class="thermal-divider"></div>
            <div class="thermal-summary">
                <div class="thermal-summary-row">
                    <span>Subtotal</span>
                    <span>Rp{{ number_format($transaction->subtotal, 0, ',', '.') }}</span>
                </div>
                @if ($transaction->tax > 0)
                <div class="thermal-summary-row">
                    <span>Pajak</span>
                    <span>Rp{{ number_format($transaction->tax, 0, ',', '.') }}</span>
                </div>
                @endif
                @if ($transaction->discount > 0)
                <div class="thermal-summary-row">
                    <span>Diskon</span>
                    <span>Rp{{ number_format($transaction->discount, 0, ',', '.') }}</span>
                </div>
                @endif
            </div>

            <!-- Total -->
            <div class="thermal-total">
                <span>TOTAL</span>
                <span>Rp{{ number_format($transaction->total_amount, 0, ',', '.') }}</span>
            </div>

            <!-- Payment Method -->
            <div class="thermal-summary">
                <div class="thermal-summary-row">
                    <span>Metode</span>
                    <span>{{ strtoupper($transaction->payment_method ?? '-') }}</span>
                </div>
            </div>

            <!-- Footer -->
            <div class="thermal-footer">
                <div class="thermal-thank-you">Terimakasih</div>
                <div class="thermal-footer-text">Atas Pembelian Anda</div>
                <div class="thermal-cashier">
                    Powered by Toko Intan POS<br>
                    {{ now()->format('Y') }}
                </div>
            </div>

            <div class="thermal-jagged"></div>
        </div>

        <!-- Buttons -->
        <div class="button-group no-print">
            <button class="btn btn-download" onclick="downloadReceipt()">
                <span>💾</span>
                Download PDF
            </button>
            <button class="btn btn-print" onclick="window.print()">
                <span>🖨️</span>
                Cetak
            </button>
        </div>

        <div class="button-group no-print">
            <a class="btn btn-back" href="{{ route('kasir.index') }}" onclick="handleBackToCashier(event)">
                Kembali ke Kasir
            </a>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        // Hide "Kembali ke Kasir" button if loaded inside an iframe (modal context)
        if (window.self !== window.top) {
            const backBtn = document.querySelector('.btn-back');
            if (backBtn && backBtn.parentElement) {
                backBtn.parentElement.style.display = 'none';
            }
        }

        function handleBackToCashier(event) {
            event.preventDefault();
            
            // If this page was opened via window.open (has an opener tab)
            if (window.opener && !window.opener.closed) {
                try {
                    // Reload the parent/main tab to update its state
                    window.opener.location.reload();
                } catch (e) {
                    console.error("Failed to reload opener page:", e);
                }
                // Close this current receipt tab/popup automatically
                window.close();
            } else {
                // Otherwise, navigate the current tab normally
                window.location.href = event.currentTarget.getAttribute('href');
            }
        }

        function downloadReceipt() {
            const element = document.querySelector('.thermal-receipt');
            const opt = {
                margin: 0,
                filename: '{{ $transaction->transaction_code }}.pdf',
                image: {
                    type: 'png',
                    quality: 0.98
                },
                html2canvas: {
                    scale: 2
                },
                jsPDF: {
                    orientation: 'portrait',
                    unit: 'mm',
                    format: [80, 210]
                }
            };

            if (typeof html2pdf === 'undefined') {
                alert('Gunakan Print untuk simpan sebagai PDF');
                window.print();
                return;
            }

            html2pdf().set(opt).from(element).save();
        }
    </script>
</body>

</html>