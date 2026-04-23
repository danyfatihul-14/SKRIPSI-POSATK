<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk {{ $transaction->transaction_code }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Courier New', monospace;
            background: #f5f5f5;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .receipt-wrapper {
            background: #fff;
            max-width: 400px;
            width: 100%;
        }

        /* Thermal Printer Style - 80mm width */
        .thermal-receipt {
            width: 100%;
            padding: 12px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.4;
            background: #fff;
            color: #000;
        }

        .thermal-header {
            text-align: center;
            margin-bottom: 10px;
            border-bottom: 1px dashed #000;
            padding-bottom: 8px;
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
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            margin-bottom: 4px;
            word-break: break-word;
        }

        .thermal-item-name {
            flex: 1;
            padding-right: 8px;
        }

        .thermal-item-qty {
            text-align: center;
            min-width: 30px;
        }

        .thermal-item-price {
            text-align: right;
            min-width: 70px;
        }

        .thermal-divider {
            border-bottom: 1px dashed #000;
            margin: 8px 0;
        }

        .thermal-summary {
            font-size: 12px;
            margin-bottom: 8px;
        }

        .thermal-summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
        }

        .thermal-total {
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 6px 0;
            margin: 8px 0;
            font-weight: bold;
            font-size: 13px;
            display: flex;
            justify-content: space-between;
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

        /* Jagged edge untuk efek struk */
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
            background-color: #f5f5f5;
        }

        /* Print styles */
        @media print {
            body {
                background: #fff;
                padding: 0;
                margin: 0;
            }

            .receipt-wrapper {
                max-width: 80mm;
                box-shadow: none;
                margin: 0;
                padding: 0;
            }

            .thermal-receipt {
                padding: 8px;
                font-size: 12px;
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

            no-print {
                display: flex;
            }
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
                Kasir: {{ $transaction->user?->name ?? '-' }}
            </div>

            <!-- Items -->
            <div class="thermal-divider"></div>
            <div class="thermal-content">
                @foreach($transaction->items as $item)
                <div class="thermal-item">
                    <div class="thermal-item-name">
                        {{ substr($item->product?->product_name ?? 'Produk', 0, 18) }}<br>
                        <span style="font-size:10px;">{{ (int)$item->quantity }} x Rp{{ number_format($item->unit_price, 0, ',', '.') }}</span>
                    </div>
                    <div class="thermal-item-price">Rp{{ number_format($item->subtotal, 0, ',', '.') }}</div>
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
            <a class="btn btn-back" href="{{ route('kasir.index') }}">
                Kembali ke Kasir
            </a>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
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
                    format: [80, 200]
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