<style>
    .trx-wrap {
        --trx-border: #e5e7eb;
        --trx-muted: #6b7280;
        --trx-bg-soft: #f8fafc;
    }

    .trx-card {
        background: #fff;
        border: 1px solid var(--trx-border);
        border-radius: 14px;
        box-shadow: 0 3px 10px rgba(15, 23, 42, .06);
        padding: 18px;
    }

    .trx-title {
        font-weight: 700;
        color: var(--dark);
        font-size: 16px;
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 14px;
    }

    .trx-label {
        display: block;
        font-size: 12px;
        font-weight: 700;
        margin-bottom: 8px;
        color: var(--dark);
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .trx-input,
    .trx-select {
        width: 100%;
        border: 1px solid var(--trx-border);
        border-radius: 10px;
        padding: 12px 14px;
        font-size: 15px;
        font-weight: 600;
        color: var(--dark);
        outline: none;
        background: #fff;
        font-family: inherit;
        transition: border-color .2s, box-shadow .2s;
    }

    .trx-input:focus,
    .trx-select:focus {
        border-color: var(--accent);
        box-shadow: 0 0 0 3px rgba(21, 23, 61, .10);
    }

    .trx-help {
        margin-top: 6px;
        font-size: 12px;
        color: var(--trx-muted);
    }

    .trx-summary-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-bottom: 14px;
        max-height: 240px;
        overflow-y: auto;
        border-bottom: 1px dashed var(--trx-border);
        padding-bottom: 12px;
    }

    .trx-breakdown {
        display: flex;
        flex-direction: column;
        gap: 6px;
        margin: 6px 0 10px;
    }

    .trx-breakdown-row {
        display: flex;
        justify-content: space-between;
        font-size: 13px;
        color: var(--trx-muted);
    }

    .trx-total-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-top: 12px;
    }

    .trx-change-box {
        background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
        border: 1px solid #cbd5e1;
        border-radius: 12px;
        padding: 16px;
    }

    .trx-submit {
        background: var(--accent);
        color: #fff;
        padding: 14px 18px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 16px;
        border: none;
        cursor: pointer;
        transition: all .2s;
        box-shadow: 0 4px 12px rgba(15, 23, 42, .18);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        font-family: inherit;
    }

    .trx-submit:hover {
        filter: brightness(1.05);
        transform: translateY(-1px);
    }

    .trx-back-btn {
        background: transparent;
        color: var(--accent);
        padding: 8px 14px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        border: 1px solid var(--accent);
        cursor: pointer;
        transition: all .2s;
        display: flex;
        align-items: center;
        gap: 8px;
        font-family: inherit;
    }

    .trx-back-btn:hover {
        background: var(--accent);
        color: #fff;
    }

    .trx-context {
        display: none;
        margin: 14px 20px 0;
        background: #eef2ff;
        border: 1px solid #c7d2fe;
        border-radius: 10px;
        padding: 12px 14px;
    }

    @media (max-width: 768px) {
        .trx-mobile-pad {
            padding: 12px 14px !important;
        }

        .trx-content-pad {
            padding: 12px !important;
        }

        .trx-card {
            padding: 14px;
            border-radius: 12px;
        }

        #quickPay {
            grid-template-columns: repeat(2, 1fr) !important;
        }

        #change {
            font-size: 26px !important;
        }

        .trx-submit {
            width: 100%;
            min-height: 46px;
        }
    }
</style>
<div id="page-transaksi" class="page h-full flex flex-col trx-wrap" style="background: var(--light);">
    <div class="trx-mobile-pad" style="padding: 16px 24px; background: #fff; border-bottom: 1px solid #e5e7eb; box-shadow: 0 1px 3px rgba(0,0,0,.04); display: flex; align-items: center; justify-content: space-between; gap: 12px;">
        <div>
            <h1 style="font-size: 18px; font-weight: 700; color: var(--dark); display: flex; align-items: center; gap: 10px;">
                <svg xmlns="http://www.w3.org/2000/svg" style="width: 22px; height: 22px; color: var(--accent);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                </svg>
                Pembayaran
            </h1>
            <p style="font-size: 12px; color: var(--dark-light); margin-top: 4px;">Selesaikan pembayaran dengan cepat dan jelas.</p>
        </div>
        <button onclick="showPage('keranjang')" class="trx-back-btn">
            <svg xmlns="http://www.w3.org/2000/svg" style="width: 16px; height: 16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Kembali
        </button>
    </div>

    <div id="paymentContextBox" class="trx-context">
        <div id="paymentContextTitle" style="font-weight:700; color:#1e1b4b; font-size:14px;"></div>
        <div id="paymentContextMeta" style="font-size:12px; color:#3730a3; margin-top:4px;"></div>
        <button type="button" id="btnClearPending" onclick="clearSelectedPendingTransaction()" style="display:none; margin-top:10px; background:#ef4444; color:#fff; border:none; border-radius:8px; padding:8px 12px; cursor:pointer; font-weight:600;">
            Batalkan pilih transaksi pending
        </button>
    </div>

    <div class="trx-content-pad" style="flex: 1; overflow-y: auto; padding: 24px;">
        <div style="max-width: 760px; margin: 0 auto; display: flex; flex-direction: column; gap: 16px;">

            <div class="trx-card">
                <h2 class="trx-title">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width: 20px; height: 20px; color: var(--accent);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Ringkasan Pesanan
                </h2>

                <div id="orderSummary" class="trx-summary-list"></div>

                <div class="trx-breakdown">
                    <div class="trx-breakdown-row">
                        <span>Subtotal</span>
                        <span id="breakdownSubtotal">Rp0</span>
                    </div>
                    <div class="trx-breakdown-row">
                        <span>Diskon</span>
                        <span id="breakdownDiscount">Rp0</span>
                    </div>
                    <div class="trx-breakdown-row">
                        <span>Pajak</span>
                        <span id="breakdownTax">Rp0</span>
                    </div>
                    <div class="trx-breakdown-row"> 
                        <span>Biaya QRIS (0.7%)</span> 
                        <span id="breakdownQrisFee">Rp0</span>
                    </div>
                </div>

                <div class="trx-total-row">
                    <span style="font-weight: 700; font-size: 18px; color: var(--dark);">Total Belanja</span>
                    <span id="orderTotal" style="font-weight: 800; font-size: 26px; color: var(--accent);">Rp0</span>
                </div>
            </div>

            <div class="trx-card">
                <h2 class="trx-title">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width: 20px; height: 20px; color: var(--accent);" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Detail Pembayaran
                </h2>

                <form id="paymentForm" action="{{ route('kasir.checkout') }}" method="POST" style="display: flex; flex-direction: column; gap: 14px;">
                    @csrf
                    <input type="hidden" name="cart" id="cartInput">
                    <input type="hidden" id="pendingTransactionId" value="">

                    <div>
                        <label class="trx-label">Metode Pembayaran</label>
                        <select id="paymentMethod" name="payment_method" class="trx-select">
                            <option value="cash">Cash</option>
                            <option value="qris">QRIS</option>
                        </select>
                    </div>

                    <div>
                        <label class="trx-label">Uang Dibayar (Rp)</label>
                        <input type="number" id="payment" name="payment" class="trx-input" min="0" required placeholder="Masukkan nominal pembayaran..." oninput="updateChange()">
                        <div class="trx-help">Masukkan nominal yang diterima dari pelanggan.</div>
                    </div>

                    <div id="paymentWarning" style="display:none; background:#fef2f2; border:1px solid #fecaca; color:#b91c1c; padding:10px 12px; border-radius:8px; font-size:12px; font-weight:600;">
                        Nominal pembayaran kurang dari total transaksi.
                    </div>

                    <div>
                        <label class="trx-label">Nominal Cepat</label>
                        <div id="quickPay" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px;"></div>
                    </div>

                    <div class="trx-change-box">
                        <div style="font-size: 12px; font-weight: 700; margin-bottom: 10px; color: #475569; text-transform: uppercase; letter-spacing: .05em;">Kembalian</div>
                        <div id="change" style="font-size: 32px; font-weight: 800; color: #0f172a;">Rp0</div>
                    </div>

                    <button id="paymentSubmitBtn" type="submit" class="trx-submit">
                        <svg xmlns="http://www.w3.org/2000/svg" style="width: 20px; height: 20px;" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" />
                        </svg>
                        <span id="paymentSubmitLabel">Proses Transaksi</span>
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div id="qrisSection" style="display: none; margin-top: 16px; padding: 16px; background: #f0f9ff; border: 2px solid #0284c7; border-radius: 12px;">
        <div style="text-align: center; margin-bottom: 12px;">
            <h3 style="color: #0284c7; font-weight: 700; margin: 0 0 8px;">📱 Scan QRIS Pembayaran</h3>
            <p style="color: #475569; font-size: 13px; margin: 0;">Arahkan aplikasi E-Wallet ke QR Code berikut</p>
        </div>
        <div id="qrisContainer" style="display: flex; justify-content: center; align-items: center; flex-direction: column; gap: 12px;">
            <div id="qrisLoading" style="text-align: center;">
                <div style="border: 3px solid #e5e7eb; border-top: 3px solid #0284c7; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto;"></div>
                <p style="color: #475569; font-size: 12px; margin-top: 8px;">Sedang membuat QR Code...</p>
            </div>
        </div>
        <p style="text-align: center; color: #64748b; font-size: 11px; margin-top: 12px;">Nominal: <strong id="qrisAmount">Rp0</strong></p>
    </div>
</div>