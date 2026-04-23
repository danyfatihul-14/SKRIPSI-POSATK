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
</style>

<div id="page-kasir" class="page h-full flex flex-col active" style="background: var(--light);">
    <div style="flex: 1; overflow: hidden; padding: 16px 24px;">
        <div id="receiptModal" class="receipt-modal hidden">
            <div class="receipt-modal__backdrop"></div>

            <div class="receipt-modal__dialog">
                <h3 class="receipt-modal__title" id="receiptModalTitle">Pembayaran Berhasil</h3>
                <p class="receipt-modal__message" id="receiptModalMessage">Struk siap dicetak.</p>

                <div class="receipt-modal__actions">
                    <button type="button" class="btn-secondary" id="receiptModalCloseBtn" onclick="closeReceiptModal()">Tutup</button>
                    <button type="button" class="btn-primary" id="receiptModalPrintBtn">Cetak Struk</button>
                </div>
            </div>
        </div>
        <div class="kasir-split">
            <div class="kasir-left">
                <div style="padding: 12px 14px; border-bottom: 1px solid var(--accent); display:flex; align-items:center; justify-content:space-between; gap:10px;">
                    <span style="font-weight:700; color:var(--dark);">Daftar Barang</span>
                    <input
                        id="productSearch"
                        type="text"
                        placeholder="Cari produk..."
                        style="width:220px; max-width:100%; border:1px solid var(--accent); border-radius:8px; padding:8px 10px; font-size:13px; outline:none;" />
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