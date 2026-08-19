<div id="page-closing" class="page" style="padding: 24px; max-width: 1200px; margin: 0 auto; width: 100%;">
    {{-- Header Section --}}
    <div style="background: #ffffff; border-radius: 16px; padding: 20px 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); margin-bottom: 24px; display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px; border: 1px solid #e2e8f0;">
        <div>
            <div style="font-size: 12px; font-weight: 700; color: #2563eb; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px;">
                📊 Laporan Penjualan Shift Kasir
            </div>
            <h2 style="font-size: 20px; font-weight: 800; color: #0f172a; margin: 0;">
                Closing Shift: <span id="closingCashierNameHeader">{{ auth()->user()->name }}</span>
            </h2>
            <div style="font-size: 13px; color: #64748b; margin-top: 4px;">
                Cabang Toko: <strong id="closingStoreNameHeader" style="color: #334155;">{{ auth()->user()->store?->name_store ?? 'Toko Intan' }}</strong>
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 10px; padding: 6px 12px; gap: 8px;">
                <label for="closingDateFilter" style="font-size: 12px; font-weight: 700; color: #475569; white-space: nowrap;">Tanggal:</label>
                <input type="date" id="closingDateFilter" value="{{ date('Y-m-d') }}" onchange="loadClosingReport()" style="border: none; background: transparent; font-size: 13px; font-weight: 700; color: #0f172a; outline: none; cursor: pointer;">
            </div>

            <button type="button" onclick="printClosingThermal()" style="background: #2563eb; color: #ffffff; border: none; padding: 10px 18px; border-radius: 10px; font-size: 13px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25); transition: all 0.2s;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 16px; height: 16px;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-19.126 0C1.068 7.441.3 8.375.3 9.456v6.294A2.25 2.25 0 002.55 18h1.091" />
                </svg>
                🖨️ Cetak Struk Closing
            </button>
        </div>
    </div>

    {{-- 4 Stat Cards --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-bottom: 24px;">
        {{-- Card 1: Total Omset --}}
        <div style="background: linear-gradient(135deg, #1e293b, #0f172a); border-radius: 16px; padding: 20px; color: white; box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.3);">
            <div style="font-size: 12px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">
                Total Omset Penjualan
            </div>
            <div id="closingTotalAmount" style="font-size: 24px; font-weight: 900; color: #38bdf8;">
                Rp 0
            </div>
            <div style="font-size: 11px; color: #cbd5e1; margin-top: 6px; display: flex; align-items: center; gap: 4px;">
                <span>🛒</span> <span id="closingTotalTransCount">0 Transaksi Selesai</span>
            </div>
        </div>

        {{-- Card 2: Cash / Tunai --}}
        <div style="background: #ffffff; border-radius: 16px; padding: 20px; border: 1px solid #10b981; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.1);">
            <div style="font-size: 12px; font-weight: 700; color: #047857; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between;">
                <span>💵 Pembayaran Tunai (Cash)</span>
                <span style="background: #d1fae5; color: #065f46; font-size: 10px; padding: 2px 8px; border-radius: 99px; font-weight: 800;">CASH</span>
            </div>
            <div id="closingTotalCash" style="font-size: 22px; font-weight: 800; color: #059669;">
                Rp 0
            </div>
            <div style="font-size: 11px; color: #6b7280; margin-top: 6px;">
                Uang fisiknya wajib ada di laci kasir
            </div>
        </div>

        {{-- Card 3: Non-Tunai / QRIS --}}
        <div style="background: #ffffff; border-radius: 16px; padding: 20px; border: 1px solid #3b82f6; box-shadow: 0 4px 15px rgba(59, 130, 246, 0.1);">
            <div style="font-size: 12px; font-weight: 700; color: #1d4ed8; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between;">
                <span>📱 Pembayaran Non-Tunai</span>
                <span style="background: #dbeafe; color: #1e40af; font-size: 10px; padding: 2px 8px; border-radius: 99px; font-weight: 800;">QRIS</span>
            </div>
            <div id="closingTotalQris" style="font-size: 22px; font-weight: 800; color: #2563eb;">
                Rp 0
            </div>
            <div style="font-size: 11px; color: #6b7280; margin-top: 6px;">
                Masuk langsung ke rekening / Midtrans
            </div>
        </div>

        {{-- Card 4: Total Qty Barang --}}
        <div style="background: #ffffff; border-radius: 16px; padding: 20px; border: 1px solid #e2e8f0; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.03);">
            <div style="font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 8px;">
                🛍️ Total Barang Terjual
            </div>
            <div id="closingTotalQtySold" style="font-size: 22px; font-weight: 800; color: #334155;">
                0 pcs
            </div>
            <div style="font-size: 11px; color: #94a3b8; margin-top: 6px;">
                Total fisik item keluar dari stok
            </div>
        </div>
    </div>

    {{-- Two Column Layout: Product Summary & Transaction History --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(450px, 1fr)); gap: 24px;">
        {{-- Product Sales Breakdown Table --}}
        <div style="background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.03); overflow: hidden; display: flex; flex-direction: column;">
            <div style="padding: 16px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 14px; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">
                    📦 Rincian Produk Terjual (Shift Ini)
                </h3>
            </div>

            <div style="max-height: 420px; overflow-y: auto; flex: 1;">
                <table style="width: 100%; border-collapse: collapse; font-size: 12px; text-align: left;">
                    <thead style="position: sticky; top: 0; background: #f1f5f9; color: #475569; font-weight: 700; border-bottom: 1px solid #cbd5e1; z-index: 10;">
                        <tr>
                            <th style="padding: 10px 16px;">Nama Produk / Varian</th>
                            <th style="padding: 10px 16px; text-align: center; width: 80px;">Qty</th>
                            <th style="padding: 10px 16px; text-align: right; width: 130px;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody id="closingProductsTableBody">
                        <tr>
                            <td colspan="3" style="padding: 24px; text-align: center; color: #94a3b8;">Memuat rincian produk...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Transaction List Table --}}
        <div style="background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.03); overflow: hidden; display: flex; flex-direction: column;">
            <div style="padding: 16px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="font-size: 14px; font-weight: 800; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px;">
                    📜 Riwayat Transaksi Shift Ini
                </h3>
            </div>

            <div style="max-height: 420px; overflow-y: auto; flex: 1;">
                <table style="width: 100%; border-collapse: collapse; font-size: 12px; text-align: left;">
                    <thead style="position: sticky; top: 0; background: #f1f5f9; color: #475569; font-weight: 700; border-bottom: 1px solid #cbd5e1; z-index: 10;">
                        <tr>
                            <th style="padding: 10px 16px;">Waktu</th>
                            <th style="padding: 10px 16px;">Struk / Pembeli</th>
                            <th style="padding: 10px 16px; text-align: center;">Metode</th>
                            <th style="padding: 10px 16px; text-align: right;">Total</th>
                        </tr>
                    </thead>
                    <tbody id="closingTransactionsTableBody">
                        <tr>
                            <td colspan="4" style="padding: 24px; text-align: center; color: #94a3b8;">Memuat riwayat transaksi...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
