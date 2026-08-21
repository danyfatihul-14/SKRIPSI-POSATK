<script>
    if (typeof window.currentClosingData === 'undefined') {
        window.currentClosingData = null;
    }
    var currentClosingData = window.currentClosingData;

    async function loadClosingReport() {
        const dateInput = document.getElementById('closingDateFilter');
        const selectedDate = dateInput ? dateInput.value : new Date().toISOString().split('T')[0];

        const productsBody = document.getElementById('closingProductsTableBody');
        const transactionsBody = document.getElementById('closingTransactionsTableBody');

        if (productsBody) productsBody.innerHTML = '<tr><td colspan="3" style="padding: 24px; text-align: center; color: #94a3b8;">⏳ Memuat laporan shift...</td></tr>';
        if (transactionsBody) transactionsBody.innerHTML = '<tr><td colspan="4" style="padding: 24px; text-align: center; color: #94a3b8;">⏳ Memuat riwayat...</td></tr>';

        try {
            const response = await fetch(`/kasir/closing-report?date=${selectedDate}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) throw new Error('Gagal memuat laporan closing shift');

            const data = await response.json();
            currentClosingData = data;

            // Render Header & Stats
            const cashierHeader = document.getElementById('closingCashierNameHeader');
            const storeHeader = document.getElementById('closingStoreNameHeader');
            if (cashierHeader) cashierHeader.innerText = data.cashier_name || 'Kasir';
            if (storeHeader) storeHeader.innerText = data.store_name || 'Toko Intan';

            const totalAmountEl = document.getElementById('closingTotalAmount');
            const totalCashEl = document.getElementById('closingTotalCash');
            const totalQrisEl = document.getElementById('closingTotalQris');
            const totalQtySoldEl = document.getElementById('closingTotalQtySold');
            const totalTransCountEl = document.getElementById('closingTotalTransCount');

            if (totalAmountEl) totalAmountEl.innerText = 'Rp ' + Number(data.total_amount || 0).toLocaleString('id-ID');
            if (totalCashEl) totalCashEl.innerText = 'Rp ' + Number(data.total_cash || 0).toLocaleString('id-ID');
            if (totalQrisEl) totalQrisEl.innerText = 'Rp ' + Number(data.total_qris || 0).toLocaleString('id-ID');
            if (totalQtySoldEl) totalQtySoldEl.innerText = Number(data.total_qty_sold || 0).toLocaleString('id-ID') + ' pcs';
            if (totalTransCountEl) totalTransCountEl.innerText = `${data.total_transactions || 0} Transaksi Selesai (${data.formatted_date || ''})`;

            // Render Product Summary
            if (productsBody) {
                if (!data.products_summary || data.products_summary.length === 0) {
                    productsBody.innerHTML = '<tr><td colspan="3" style="padding: 24px; text-align: center; color: #94a3b8;">Belum ada produk terjual pada shift ini.</td></tr>';
                } else {
                    productsBody.innerHTML = data.products_summary.map(p => `
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 10px 16px; font-weight: 600; color: #1e293b;">${escapeHtml(p.name)}</td>
                            <td style="padding: 10px 16px; text-align: center; font-weight: 700; color: #2563eb;">${p.qty}</td>
                            <td style="padding: 10px 16px; text-align: right; font-weight: 700; color: #0f172a;">Rp ${Number(p.subtotal).toLocaleString('id-ID')}</td>
                        </tr>
                    `).join('');
                }
            }

            // Render Transactions Table
            if (transactionsBody) {
                if (!data.transactions || data.transactions.length === 0) {
                    transactionsBody.innerHTML = '<tr><td colspan="4" style="padding: 24px; text-align: center; color: #94a3b8;">Belum ada transaksi selesai pada tanggal ini.</td></tr>';
                } else {
                    transactionsBody.innerHTML = data.transactions.map(t => `
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 10px 16px; font-weight: 600; color: #64748b;">${t.created_at}</td>
                            <td style="padding: 10px 16px;">
                                <div style="font-weight: 700; color: #2563eb;">#${t.transaction_code}</div>
                                <div style="font-size: 11px; color: #64748b;">Kasir: ${escapeHtml(t.cashier_name || '-')} | Pembeli: ${escapeHtml(t.customer_name)}</div>
                            </td>
                            <td style="padding: 10px 16px; text-align: center;">
                                <span style="display: inline-block; font-size: 10px; font-weight: 800; padding: 2px 8px; border-radius: 99px; ${t.payment_method === 'QRIS' ? 'background: #dbeafe; color: #1e40af;' : 'background: #d1fae5; color: #065f46;'}">
                                    ${t.payment_method}
                                </span>
                            </td>
                            <td style="padding: 10px 16px; text-align: right; font-weight: 800; color: #0f172a;">
                                Rp ${Number(t.total_amount).toLocaleString('id-ID')}
                            </td>
                        </tr>
                    `).join('');
                }
            }
        } catch (error) {
            console.error('Error loading closing report:', error);
            if (productsBody) productsBody.innerHTML = `<tr><td colspan="3" style="padding: 16px; text-align: center; color: #ef4444;">${error.message}</td></tr>`;
            if (transactionsBody) transactionsBody.innerHTML = `<tr><td colspan="4" style="padding: 16px; text-align: center; color: #ef4444;">${error.message}</td></tr>`;
        }
    }

    function printClosingThermal() {
        const dateInput = document.getElementById('closingDateFilter');
        const selectedDate = dateInput ? dateInput.value : new Date().toISOString().split('T')[0];
        const receiptUrl = `/kasir/closing-receipt?date=${selectedDate}&print=1`;

        if (typeof window.askPrintReceipt === 'function') {
            window.askPrintReceipt(receiptUrl);
        } else {
            window.open(receiptUrl, '_blank', 'width=420,height=760');
        }
    }
</script>
