<style>
    #page-kasir .kasir-split {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 360px;
        gap: 16px;
        height: 100%;
    }

    #page-kasir .kasir-left {
        min-width: 0;
        background: white;
        border: 1px solid var(--accent);
        border-radius: 12px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    #page-kasir .kasir-right {
        background: white;
        border: 1px solid var(--accent);
        border-radius: 12px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    #page-kasir .kasir-left,
    #page-kasir .kasir-right {
        outline: 1px solid #d5d9e2;
        outline-offset: 0;
        border: none;
        border-radius: 12px;
    }

    #page-kasir .kasir-left>div:first-child,
    #page-kasir .kasir-right>div:first-child {
        border-bottom: none !important;
    }

    #page-kasir .product-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 16px;
    }

    #page-kasir .product-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, .08);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        border: 1px solid var(--accent);
        transition: all .2s;
        cursor: pointer;
    }

    #page-kasir .product-info {
        padding: 12px;
        display: flex;
        flex-direction: column;
        flex: 1;
    }

    #page-kasir .product-actions {
        display: flex;
        gap: 8px;
        margin-top: auto;
    }

    #page-kasir .product-actions button {
        flex: 1;
        min-height: 36px;
    }

    #page-kasir .cart-footer {
        margin-top: auto;
        border-top: 1px solid #e8ecf3;
        padding: 14px;
        background: #fff;
    }

    #page-kasir .cart-actions {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    #page-kasir .cart-actions button {
        width: 100%;
        min-height: 40px;
    }

    #page-kasir .quick-cart-empty-state {
        flex: 1;
        min-height: 220px;
        padding: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    #page-kasir .quick-cart-empty-inner {
        text-align: center;
        color: var(--dark-light);
    }

    #page-kasir .quick-cart-empty-icon {
        width: 64px;
        height: 64px;
        margin: 0 auto 10px;
        border-radius: 16px;
        background: #f8fafc;
        border: 1px solid #e5e7eb;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    #page-kasir .quick-cart-empty-title {
        font-size: 14px;
        font-weight: 700;
        color: var(--dark);
        margin-bottom: 4px;
    }

    #page-kasir .quick-cart-empty-subtitle {
        font-size: 12px;
    }

    .receipt-modal {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .receipt-modal.hidden {
        display: none;
    }

    .receipt-modal__backdrop {
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, 0.45);
    }

    .receipt-modal__dialog {
        position: relative;
        z-index: 1;
        width: min(92vw, 420px);
        background: #fff;
        border-radius: 14px;
        padding: 20px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
        text-align: center;
    }

    .receipt-modal__title {
        margin: 0 0 8px;
        font-size: 18px;
        font-weight: 700;
        color: #15173D;
    }

    .receipt-modal__message {
        margin: 0 0 18px;
        font-size: 14px;
        color: #4b5563;
    }

    .receipt-modal__actions {
        display: flex;
        gap: 10px;
        justify-content: center;
    }

    .btn-primary,
    .btn-secondary {
        border: 0;
        border-radius: 10px;
        padding: 10px 14px;
        font-weight: 600;
        cursor: pointer;
    }

    .btn-primary {
        background: #15173D;
        color: #fff;
    }

    .btn-secondary {
        background: #e5e7eb;
        color: #111827;
    }

    @media (max-width: 1024px) {
        #page-kasir .kasir-split {
            grid-template-columns: minmax(0, 1fr) 300px;
            gap: 12px;
        }
    }

    @media (max-width: 768px) {
        #page-kasir .kasir-split {
            grid-template-columns: 1fr;
        }

        #page-kasir .product-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
        }

        #page-kasir .product-info {
            padding: 10px;
        }
    }

    @media (max-width: 480px) {
        #page-kasir .product-grid {
            grid-template-columns: 1fr;
            gap: 10px;
        }

        #page-kasir .product-actions {
            flex-direction: column;
        }

        #page-kasir .product-actions button {
            width: 100%;
        }
    }

    /* Modal Pemilihan Varian */
    .variant-modal {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .variant-modal.hidden {
        display: none !important;
    }

    .variant-modal__backdrop {
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, 0.45);
        backdrop-filter: blur(4px);
    }

    .variant-modal__dialog {
        position: relative;
        z-index: 1;
        width: min(92vw, 480px);
        background: #fff;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        display: flex;
        flex-direction: column;
        gap: 16px;
        animation: variantModalZoomIn 0.2s ease-out;
    }

    @keyframes variantModalZoomIn {
        from {
            opacity: 0;
            transform: scale(0.95);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    .variant-modal__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 12px;
    }

    .variant-modal__title {
        font-size: 16px;
        font-weight: 700;
        color: var(--dark);
        margin: 0;
    }

    .variant-modal__close {
        border: none;
        background: transparent;
        font-size: 20px;
        cursor: pointer;
        color: #94a3b8;
        padding: 4px;
        line-height: 1;
    }

    .variant-modal__close:hover {
        color: #64748b;
    }

    .variant-options-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 12px;
        max-height: 240px;
        overflow-y: auto;
        padding: 4px;
    }

    @media (max-width: 480px) {
        .variant-options-grid {
            grid-template-columns: 1fr;
        }
    }

    .variant-option-card {
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 12px;
        cursor: pointer;
        transition: all 0.2s;
        position: relative;
        background: #fff;
    }

    .variant-option-card:hover {
        border-color: #cbd5e1;
        background: #f8fafc;
    }

    .variant-qty-selector {
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #f8fafc;
        padding: 12px;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
    }

    .variant-qty-btn {
        width: 32px;
        height: 32px;
        border: 1px solid var(--accent);
        background: white;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 700;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.15s;
    }

    .variant-qty-btn:hover {
        background: #f1f5f9;
    }

    .variant-qty-input {
        width: 60px;
        text-align: center;
        border: none;
        background: transparent;
        font-weight: 700;
        font-size: 16px;
        outline: none;
    }

    .variant-modal__footer {
        display: flex;
        gap: 12px;
        border-top: 1px solid #f1f5f9;
        padding-top: 16px;
    }

    .variant-modal__footer button {
        flex: 1;
        padding: 10px;
        font-weight: 700;
        border-radius: 8px;
        cursor: pointer;
    }
</style>

<div id="page-kasir" class="page h-full flex flex-col active" style="background: var(--light);">
    <div style="flex: 1; overflow: hidden; padding: 16px 24px;">
        <div class="kasir-split">
            <div class="kasir-left">
                <div style="padding: 12px 14px; border-bottom: 1px solid var(--accent); display:flex; align-items:center; justify-content:space-between; gap:10px;">
                    <span style="font-weight:700; color:var(--dark);">Daftar Barang</span>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <button type="button" onclick="openAddProductModal()" style="border: 1px solid var(--accent); background: var(--accent); color: white; border-radius: 8px; padding: 8px 12px; font-size: 13px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 4px; transition: opacity .15s;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                            <svg xmlns="http://www.w3.org/2000/svg" style="width: 14px; height: 14px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                            Tambah Produk
                        </button>
                        <button type="button" onclick="openQrScanModal()" style="border: 1px solid var(--accent); background: white; color: var(--accent); border-radius: 8px; padding: 8px; font-size: 13px; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; width: 34px; height: 34px; transition: opacity .15s; margin-right: 2px;" title="Scan QR Code / Barcode">
                            <svg xmlns="http://www.w3.org/2000/svg" style="width: 18px; height: 18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 9h6V3H3v6zm0 12h6v-6H3v6zm12-12h6V3h-6v6zm3 10a1 1 0 100-2 1 1 0 000 2zm-3-1a1 1 0 11-2 0 1 1 0 012 0zm3 3a1 1 0 11-2 0 1 1 0 012 0zm-3 1a1 1 0 11-2 0 1 1 0 012 0z" />
                            </svg>
                        </button>
                        <input
                            id="productSearch"
                            type="text"
                            placeholder="Cari produk..."
                            style="width:200px; max-width:100%; border:1px solid var(--accent); border-radius:8px; padding:8px 10px; font-size:13px; outline:none;" />
                    </div>
                </div>
                <div style="flex:1; overflow-y:auto; padding: 16px;">
                    <div id="productList" class="product-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 16px;"></div>
                </div>
            </div>

            <div class="kasir-right">
                <div style="padding: 12px 14px; border-bottom: 1px solid var(--accent); display:flex; justify-content:space-between; align-items:center;">
                    <span style="font-weight:700; color:var(--dark);">Keranjang</span>
                    <span id="quickCartCount" style="font-size:12px; font-weight:700; color:var(--accent);">0 item</span>
                </div>

                <div id="quickCartEmpty" class="quick-cart-empty-state">
                    <div class="quick-cart-empty-inner">
                        <div class="quick-cart-empty-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" style="width:30px; height:30px; color:#94a3b8;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13l-1.2 5.2A1 1 0 007.8 20h10.4a1 1 0 001-.8L21 9M9 20a1 1 0 100-2 1 1 0 000 2zm9 0a1 1 0 100-2 1 1 0 000 2z" />
                            </svg>
                        </div>
                        <div class="quick-cart-empty-title">Keranjang masih kosong</div>
                        <div class="quick-cart-empty-subtitle">Klik produk di sebelah kiri untuk menambah item</div>
                    </div>
                </div>

                <div id="quickCartList" style="display:none; flex:1; overflow-y:auto; padding:12px; gap:10px; flex-direction:column; align-items:center;"></div>
                <div class="cart-footer">
                    <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                        <span style="font-size:13px; color:var(--dark-light);">Total</span>
                        <strong id="quickCartTotal" style="color:var(--accent);">Rp0</strong>
                    </div>

                    <div style="margin-bottom:12px;">
                        <label style="display:block; font-size:12px; font-weight:700; color:var(--dark); margin-bottom:6px; text-transform:uppercase; letter-spacing:.04em;">Nama Pembeli</label>
                        <input 
                            type="text" 
                            id="quickCustomerName" 
                            placeholder="Masukkan nama pembeli..." 
                            value="Pelanggan Umum"
                            style="width:100%; border:1px solid var(--accent); border-radius:8px; padding:10px; font-size:14px; font-weight:600; outline:none;" />
                    </div>

                    <div class="cart-actions">
                        <button type="button" onclick="quickHoldOrder()" style="border:1px solid #f59e0b; background:#fff7ed; color:#c2410c; border-radius:8px; padding:10px; font-weight:700; cursor:pointer;">
                            Masukkan Hold Order
                        </button>
                        <button type="button" onclick="quickSendToWaitingPayment()" style="border:1px solid var(--accent); background:var(--accent); color:#fff; border-radius:8px; padding:10px; font-weight:700; cursor:pointer;">
                            Kirim ke Waiting Payment
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Pemilihan Varian -->
<div id="variantModal" class="variant-modal hidden">
    <div class="variant-modal__backdrop" onclick="closeVariantModal()"></div>
    <div class="variant-modal__dialog">
        <div class="variant-modal__header">
            <h5 id="variantModalTitle" class="variant-modal__title">Pilih Varian</h5>
            <button type="button" class="variant-modal__close" onclick="closeVariantModal()">×</button>
        </div>
        <div style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: .04em;">Varian Tersedia</div>
        <div id="variantOptionsContainer" class="variant-options-grid">
            <!-- Pilihan varian dimuat secara dinamis via JS -->
        </div>
        
        <div style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; margin-top: 4px;">Kuantitas</div>
        <div class="variant-qty-selector">
            <span style="font-size: 14px; font-weight: 600; color: var(--dark);">Jumlah</span>
            <div style="display: flex; align-items: center; gap: 8px;">
                <button type="button" class="variant-qty-btn" onclick="updateVariantQty(-1)">-</button>
                <input type="text" id="variantQtyInput" class="variant-qty-input" value="1" readonly />
                <button type="button" class="variant-qty-btn" onclick="updateVariantQty(1)">+</button>
            </div>
        </div>
        
        <div id="variantStockWarning" style="display: none; color: #ef4444; font-size: 12px; font-weight: 600; text-align: center;">
            Stok tidak mencukupi!
        </div>

        <div class="variant-modal__footer">
            <button type="button" class="btn-secondary" onclick="closeVariantModal()" style="border: 1px solid #cbd5e1; background: #fff;">Batal</button>
            <button id="variantModalAddBtn" type="button" class="btn-primary" onclick="addSelectedVariantToCart()">Tambah ke Keranjang</button>
        </div>
    </div>
</div>

<!-- Modal Tambah Produk Baru -->
<div id="addProductModal" class="variant-modal hidden">
    <div class="variant-modal__backdrop"></div>
    <div class="variant-modal__dialog">
        <div class="variant-modal__header">
            <h5 class="variant-modal__title">Tambah Produk Baru</h5>
            <button type="button" class="variant-modal__close" onclick="closeAddProductModal()">×</button>
        </div>
        
        <form id="addProductForm" onsubmit="submitNewProduct(event)" style="display: flex; flex-direction: column; gap: 12px; text-align: left;">
            @csrf
            <div>
                <label style="display:block; font-size:11px; font-weight:700; color:#64748b; margin-bottom:4px; text-transform:uppercase; text-align: left;">Nama Produk</label>
                <input type="text" id="addProductName" required placeholder="Masukkan nama produk..." 
                    style="width:100%; border:1px solid var(--accent); border-radius:8px; padding:8px 10px; font-size:13px; outline:none;" />
            </div>

            <div>
                <label style="display:block; font-size:11px; font-weight:700; color:#64748b; margin-bottom:4px; text-transform:uppercase; text-align: left;">Kategori</label>
                <select id="addProductCategory" 
                    style="width:100%; border:1px solid var(--accent); border-radius:8px; padding:8px 10px; font-size:13px; outline:none; background:white;">
                    <option value="">Pilih Kategori (opsional)...</option>
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div>
                    <label style="display:block; font-size:11px; font-weight:700; color:#64748b; margin-bottom:4px; text-transform:uppercase; text-align: left;">Varian</label>
                    <input type="text" id="addProductVariant" placeholder="Contoh: Merah (opsional)" 
                        style="width:100%; border:1px solid var(--accent); border-radius:8px; padding:8px 10px; font-size:13px; outline:none;" />
                </div>
                <div>
                    <label style="display:block; font-size:11px; font-weight:700; color:#64748b; margin-bottom:4px; text-transform:uppercase; text-align: left;">Satuan</label>
                    <select id="addProductUnit" 
                        style="width:100%; border:1px solid var(--accent); border-radius:8px; padding:8px 10px; font-size:13px; outline:none; background:white; cursor:pointer;">
                        <option value="biji">biji</option>
                        <option value="lusin">lusin</option>
                        <option value="pack">pack</option>
                        <option value="box">box</option>
                        <option value="lembar">lembar</option>
                        <option value="buah">buah</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div>
                    <label style="display:block; font-size:11px; font-weight:700; color:#64748b; margin-bottom:4px; text-transform:uppercase; text-align: left;">Harga Jual (Rp)</label>
                    <input type="number" id="addProductSellingPrice" required min="0" placeholder="Contoh: 15000" 
                        style="width:100%; border:1px solid var(--accent); border-radius:8px; padding:8px 10px; font-size:13px; outline:none;" />
                </div>
                <div>
                    <label style="display:block; font-size:11px; font-weight:700; color:#64748b; margin-bottom:4px; text-transform:uppercase; text-align: left;">Stok Awal</label>
                    <input type="number" id="addProductInitialStock" min="0" value="0" 
                        style="width:100%; border:1px solid var(--accent); border-radius:8px; padding:8px 10px; font-size:13px; outline:none;" />
                </div>
            </div>

            <div>
                <label style="display:block; font-size:11px; font-weight:700; color:#64748b; margin-bottom:4px; text-transform:uppercase; text-align: left;">Gambar Produk</label>
                
                {{-- Toggle Buttons --}}
                <div style="display: flex; gap: 8px; margin-bottom: 8px;">
                    <button type="button" id="btnModeUpload" onclick="switchImageMode('upload')" 
                        style="flex: 1; padding: 6px; font-size: 11px; font-weight: 700; border-radius: 6px; border: 1px solid var(--accent); background: var(--accent); color: white; cursor: pointer; transition: all 0.2s;">
                        Upload File
                    </button>
                    <button type="button" id="btnModeCamera" onclick="switchImageMode('camera')" 
                        style="flex: 1; padding: 6px; font-size: 11px; font-weight: 700; border-radius: 6px; border: 1px solid #cbd5e1; background: white; color: #4b5563; cursor: pointer; transition: all 0.2s;">
                        Ambil Foto (Kamera)
                    </button>
                </div>

                {{-- Upload File Container --}}
                <div id="containerUploadImage">
                    <input type="file" id="addProductImage" accept="image/*" 
                        style="width:100%; border:1px solid var(--accent); border-radius:8px; padding:8px 10px; font-size:13px; outline:none; background:white;" />
                </div>

                {{-- Camera Container --}}
                <div id="containerCameraImage" style="display: none; flex-direction: column; gap: 8px;">
                    <div style="position: relative; width: 100%; background: #000; border-radius: 8px; overflow: hidden; min-height: 150px; display: flex; align-items: center; justify-content: center;">
                        <video id="cameraVideo" autoplay playsinline style="width: 100%; height: 150px; object-fit: cover; display: none;"></video>
                        <img id="capturedImage" style="width: 100%; height: 150px; object-fit: cover; display: none;" />
                        <span id="cameraPlaceholder" style="color: #94a3b8; font-size: 12px; font-weight: 600;">Kamera Belum Aktif</span>
                    </div>

                    <div style="display: flex; gap: 6px;">
                        <button type="button" id="btnStartCamera" onclick="startCamera()" 
                            style="flex: 1; padding: 6px; font-size: 11px; font-weight: 700; border: 1px solid var(--accent); background: white; color: var(--accent); border-radius: 6px; cursor: pointer;">
                            Aktifkan Kamera
                        </button>
                        <button type="button" id="btnCapturePhoto" onclick="capturePhoto()" disabled 
                            style="flex: 1; padding: 6px; font-size: 11px; font-weight: 700; border: 1px solid #10b981; background: #10b981; color: white; border-radius: 6px; cursor: pointer; opacity: 0.5;">
                            Ambil Foto
                        </button>
                        <button type="button" id="btnResetPhoto" onclick="resetPhoto()" style="display: none; padding: 6px 12px; font-size: 11px; font-weight: 700; border: 1px solid #ef4444; background: #ef4444; color: white; border-radius: 6px; cursor: pointer;">
                            Ulangi
                        </button>
                    </div>
                    <canvas id="cameraCanvas" style="display: none;"></canvas>
                </div>
            </div>

            <div class="variant-modal__footer" style="margin-top: 8px;">
                <button type="button" class="btn-secondary" onclick="closeAddProductModal()" style="border: 1px solid #cbd5e1; background: #fff; flex: 1;">Batal</button>
                <button type="submit" class="btn-primary" style="flex: 1;">Simpan Produk</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Scanner QR Code / Barcode -->
<div id="qrScanModal" class="receipt-modal hidden" style="z-index: 10000;">
    <div class="receipt-modal__backdrop" onclick="closeQrScanModal()"></div>
    <div class="receipt-modal__dialog" style="width: min(92vw, 480px); background: #fff; border-radius: 16px; padding: 20px; z-index: 10001; position: relative;">
        <div style="display: flex; align-items: center; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 16px;">
            <h5 style="font-size: 16px; font-weight: 700; color: var(--dark); margin: 0;">Scan QR Code / Barcode Barang</h5>
            <button type="button" onclick="closeQrScanModal()" style="border: none; background: transparent; font-size: 20px; cursor: pointer; color: #94a3b8; line-height: 1;">×</button>
        </div>
        
        <div id="reader" style="width: 100%; max-width: 440px; margin: 0 auto; background: #f8fafc; border-radius: 12px; overflow: hidden; border: 1px solid #e2e8f0;"></div>
        
        <div style="margin-top: 16px; text-align: center; font-size: 13px; color: #64748b;">
            Arahkan kamera ke QR Code atau Barcode pada label barang.
        </div>
        
        <div style="margin-top: 20px; display: flex; gap: 10px;">
            <button type="button" onclick="closeQrScanModal()" style="flex: 1; border: 1px solid #cbd5e1; background: #fff; padding: 10px; font-weight: 700; border-radius: 8px; cursor: pointer;">Tutup</button>
        </div>
    </div>
</div>