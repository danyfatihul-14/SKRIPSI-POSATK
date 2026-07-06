<div id="page-cetak-qr" class="page flex-col" style="display: none; padding: 24px; background: #f8fafc; overflow-y: auto; height: 100%;">
    <div class="page-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
        <div>
            <h1 style="font-size: 22px; font-weight: 800; color: #1e1b4b; margin: 0;">🏷️ Cetak QR Masal</h1>
            <p style="font-size: 13px; color: #64748b; margin: 4px 0 0 0;">Pilih produk yang ingin Anda cetak label barcodenya sekaligus.</p>
        </div>
        <button onclick="printSelectedQrs()" style="background: #8b5cf6; border: none; padding: 10px 22px; border-radius: 10px; font-size: 13px; font-weight: 700; color: white; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; box-shadow: 0 4px 12px rgba(139, 92, 246, 0.25); transition: background 0.2s;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 16px; height: 16px;"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.89l-1.92-1.92L2.88 13.89M17.28 13.89l1.92-1.92 1.92 1.92M9 19.5h6a2.25 2.25 0 002.25-2.25V15M9 19.5a2.25 2.25 0 01-2.25-2.25V15m12 4.5a2.25 2.25 0 002.25-2.25V15m-15 0h15M9 4.5h6a2.25 2.25 0 012.25 2.25V9M9 4.5A2.25 2.25 0 006.75 6.75V9m10.5-2.25A2.25 2.25 0 0119.5 9m0 0v6M4.5 9v6M9 9h6" /></svg>
            Cetak QR Terpilih
        </button>
    </div>

    <div class="card" style="background: white; border-radius: 16px; border: 1px solid #e2e8f0; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; flex-direction: column; flex: 1; overflow: hidden;">
        <!-- Filter bar -->
        <div style="display: flex; gap: 16px; align-items: center; margin-bottom: 20px; flex-wrap: wrap; border-bottom: 1px solid #f1f5f9; padding-bottom: 16px;">
            <div style="flex: 1; min-width: 250px;">
                <input type="text" id="qrSearchInput" oninput="filterQrProducts()" placeholder="Cari nama produk atau varian..." style="width: 100%; border: 1px solid #cbd5e1; border-radius: 10px; padding: 10px 16px; font-size: 13px; outline: none; transition: border-color 0.2s;" />
            </div>

            <div style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: #475569;">
                <span style="font-weight: 700;">Ukuran Barcode:</span>
                <select id="labelSizeSelect" style="border: 1px solid #cbd5e1; border-radius: 10px; padding: 8px 12px; font-size: 13px; outline: none; cursor: pointer; background: white;">
                    <option value="50">50mm x 30mm (Kecil - Pensil/Pulpen)</option>
                    <option value="80">80mm x 40mm (Sedang - Buku/Lainnya)</option>
                </select>
            </div>
            
            <label style="display: inline-flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 700; color: #475569; cursor: pointer; user-select: none;">
                <input type="checkbox" id="selectAllQr" onchange="toggleSelectAllQr(this)" style="width: 18px; height: 18px; border-radius: 4px; border: 1px solid #cbd5e1; cursor: pointer; accent-color: #8b5cf6;" />
                Pilih Semua
            </label>
        </div>

        <!-- Products List -->
        <div id="qrProductContainer" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 14px; overflow-y: auto; flex: 1; padding: 4px;">
            <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #94a3b8; font-size: 13px;">
                Memuat daftar produk...
            </div>
        </div>
    </div>
</div>
