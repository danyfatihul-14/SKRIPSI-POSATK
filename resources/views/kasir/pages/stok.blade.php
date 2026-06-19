{{-- filepath: e:\Polinema\Semester8\Skripsi\pos\resources\views\kasir\pages\stok.blade.php --}}
<style>
    #page-stok .stok-layout {
        display: flex;
        flex-direction: column;
        height: 100%;
        padding: 16px 24px;
        overflow-y: auto;
    }

    #page-stok .stok-header {
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    #page-stok .stok-tabs {
        display: flex;
        border-bottom: 2px solid #e2e8f0;
        margin-bottom: 20px;
        gap: 24px;
    }

    #page-stok .stok-tab-btn {
        padding: 10px 4px;
        font-size: 17px;
        font-weight: 700;
        color: #64748b;
        background: none;
        border: none;
        cursor: pointer;
        position: relative;
        transition: all 0.2s;
    }

    #page-stok .stok-tab-btn:hover {
        color: var(--accent);
    }

    #page-stok .stok-tab-btn.active {
        color: var(--accent);
    }

    #page-stok .stok-tab-btn.active::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        right: 0;
        height: 2.5px;
        background: var(--accent);
        border-radius: 4px;
    }

    #page-stok .stok-panel {
        display: none;
        background: white;
        border: 1px solid #d5d9e2;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, .05);
        padding: 20px;
    }

    #page-stok .stok-panel.active {
        display: block;
    }

    #page-stok .stok-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }

    #page-stok .stok-table th,
    #page-stok .stok-table td {
        padding: 12px 16px;
        border-bottom: 1px solid #f1f5f9;
        font-size: 16px;
    }

    #page-stok .stok-table th {
        background: #f8fafc;
        color: #475569;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 14px;
        letter-spacing: 0.05em;
    }

    #page-stok .stok-table tr:hover td {
        background: #f8fafc;
    }

    #page-stok .badge-unit {
        background: #e2e8f0;
        color: #475569;
        font-weight: 600;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 13px;
    }

    #page-stok .stok-search-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
        gap: 12px;
    }

    #page-stok .form-group {
        margin-bottom: 16px;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    #page-stok .form-group label {
        font-size: 15px;
        font-weight: 700;
        color: #334155;
    }

    #page-stok .form-control {
        width: 100%;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        padding: 10px 12px;
        font-size: 16px;
        outline: none;
        transition: all 0.2s;
    }

    #page-stok .form-control:focus {
        border-color: var(--accent);
        box-shadow: 0 0 0 3px rgba(21, 23, 61, 0.1);
    }

    #page-stok .info-box {
        background: #f0f9ff;
        border: 1px solid #bae6fd;
        color: #0369a1;
        padding: 12px;
        border-radius: 8px;
        font-size: 15px;
        display: flex;
        gap: 8px;
        align-items: center;
        margin-top: 10px;
    }

    /* Modal Styling */
    .stok-modal {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .stok-modal.hidden {
        display: none !important;
    }

    .stok-modal__backdrop {
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, 0.45);
        backdrop-filter: blur(4px);
    }

    .stok-modal__dialog {
        position: relative;
        z-index: 1;
        width: min(92vw, 420px);
        background: #fff;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        display: flex;
        flex-direction: column;
        gap: 16px;
        animation: stokModalZoomIn 0.2s ease-out;
    }

    @keyframes stokModalZoomIn {
        from {
            opacity: 0;
            transform: scale(0.95);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    .stok-modal__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 12px;
    }

    .stok-modal__title {
        font-size: 18px;
        font-weight: 700;
        color: var(--dark);
        margin: 0;
    }

    .stok-modal__close {
        border: none;
        background: transparent;
        font-size: 20px;
        cursor: pointer;
        color: #94a3b8;
        padding: 4px;
        line-height: 1;
    }

    .stok-modal__close:hover {
        color: #64748b;
    }

    .stok-modal__footer {
        display: flex;
        gap: 12px;
        border-top: 1px solid #f1f5f9;
        padding-top: 16px;
    }

    .stok-modal__footer button {
        flex: 1;
        padding: 10px;
        font-weight: 700;
        border-radius: 8px;
        cursor: pointer;
    }
</style>

<div id="page-stok" class="page h-full flex flex-col" style="background: var(--light);">
    <div class="stok-layout">
        <div class="stok-header">
            <h2 style="font-weight: 700; color: var(--dark); font-size: 22px; margin: 0;">Pengelolaan Stok</h2>
        </div>

        <div class="stok-tabs">
            <button class="stok-tab-btn active" onclick="switchStokTab('adjust')">Penyesuaian Stok</button>
            <button class="stok-tab-btn" onclick="switchStokTab('convert')">Konversi Satuan (Lusin -> Biji)</button>
        </div>

        <!-- TAB ADJUST -->
        <div id="panel-stok-adjust" class="stok-panel active">
            <div class="stok-search-container">
                <input
                    id="stockSearchInput"
                    type="text"
                    placeholder="Cari produk untuk disesuaikan..."
                    class="form-control"
                    style="max-width: 320px;" />
            </div>
            
            <div style="overflow-x: auto;">
                <table class="stok-table">
                    <thead>
                        <tr>
                            <th>Nama Produk</th>
                            <th>Varian</th>
                            <th>Satuan</th>
                            <th style="text-align: center;">Stok Saat Ini</th>
                            <th style="text-align: center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="stockTableBody">
                        <!-- Data dimasukkan secara dinamis -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- TAB CONVERT -->
        <div id="panel-stok-convert" class="stok-panel">
            <form id="stockConvertForm" onsubmit="handleStockConvert(event)" style="max-width: 500px;">
                <div class="form-group">
                    <label for="convertSourceProduct">Produk Asal (Yang Ingin Dipecah / Dikurangi)</label>
                    <select id="convertSourceProduct" class="form-control" required onchange="updateConvertPreview()">
                        <option value="">-- Pilih Produk Asal --</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="convertTargetProduct">Produk Tujuan (Yang Ingin Ditambah)</label>
                    <select id="convertTargetProduct" class="form-control" required onchange="updateConvertPreview()">
                        <option value="">-- Pilih Produk Tujuan --</option>
                    </select>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                    <div class="form-group">
                        <label for="convertQty">Jumlah Produk Asal</label>
                        <input id="convertQty" type="number" min="1" value="1" class="form-control" required oninput="updateConvertPreview()" />
                    </div>
                    <div class="form-group">
                        <label for="convertRatio">Rasio Konversi (Perkalian)</label>
                        <input id="convertRatio" type="number" min="1" value="12" class="form-control" required oninput="updateConvertPreview()" />
                        <span style="font-size: 13px; color: #64748b;">Contoh: 12 jika Lusin -> Biji</span>
                    </div>
                </div>

                <div id="convertPreviewBox" class="info-box" style="display: none;">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width:20px; height:20px; flex-shrink: 0;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div id="convertPreviewText">Pratinjau konversi akan ditampilkan di sini.</div>
                </div>

                <button type="submit" class="btn-primary" style="margin-top: 20px; width: 100%; border: none; padding: 12px; border-radius: 8px; font-weight: 700; cursor: pointer;">
                    Konversi Sekarang
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Modal Penyesuaian Stok Manual -->
<div id="stockAdjustModal" class="stok-modal hidden">
    <div class="stok-modal__backdrop" onclick="closeStockAdjustModal()"></div>
    <div class="stok-modal__dialog">
        <div class="stok-modal__header">
            <h5 id="stockAdjustModalTitle" class="stok-modal__title">Update Stok</h5>
            <button type="button" class="stok-modal__close" onclick="closeStockAdjustModal()">×</button>
        </div>
        
        <div style="font-size: 15px; color: #475569; font-weight: 600;">
            Produk: <span id="adjustModalProductName" style="color: var(--dark); font-weight: 700;">-</span>
        </div>

        <div style="display: flex; align-items: center; justify-content: space-between; background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0; margin-top: 4px;">
            <span style="font-size: 16px; font-weight: 600; color: #64748b;">Stok Saat Ini:</span>
            <strong id="adjustModalCurrentQty" style="font-size: 18px; color: var(--dark); font-weight: 700;">0</strong>
        </div>

        <div class="form-group" style="margin-top: 8px;">
            <label for="adjustModalNewQty">Masukkan Jumlah Stok Baru</label>
            <input type="number" id="adjustModalNewQty" class="form-control" min="0" placeholder="Masukkan jumlah stok baru..." required />
        </div>

        <div class="stok-modal__footer">
            <button type="button" class="btn-secondary" onclick="closeStockAdjustModal()" style="border: 1px solid #cbd5e1; background: #fff;">Batal</button>
            <button type="button" class="btn-primary" onclick="saveStockAdjustment()" style="border: none;">Simpan Perubahan</button>
        </div>
    </div>
</div>
