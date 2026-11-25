<script>
    const historyIsWaiter = document.querySelector('.main-container')?.dataset.isWaiter === '1';
    var allTransactions = window.allTransactions || [];
    var filteredTransactions = window.filteredTransactions || [];

    async function loadTransactionHistory() {
        const historyList = document.getElementById('historyList');
        if (!historyList) return;

        try {
            historyList.innerHTML = '<div class="history-loading"><p>Memuat riwayat transaksi...</p></div>';

            const response = await fetch('{{ route("kasir.transaction-history") }}', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) throw new Error('Gagal memuat history');

            const data = await response.json();
            allTransactions = Array.isArray(data.transactions) ? data.transactions : [];
            filteredTransactions = [...allTransactions];

            if (allTransactions.length === 0) {
                historyList.innerHTML = `
                    <div class="history-empty">
                        <div class="history-empty-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <p class="history-empty-title">Belum ada riwayat transaksi</p>
                        <p>Transaksi yang sudah selesai akan ditampilkan di sini</p>
                    </div>
                `;
                return;
            }

            renderHistoryList(filteredTransactions);
        } catch (error) {
            console.error('Error loading history:', error);
            historyList.innerHTML = `
                <div class="history-empty">
                    <div class="history-empty-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4v2m0 6v2m0-14V5m0 2h2m-2 0h-2" />
                        </svg>
                    </div>
                    <p class="history-empty-title">Gagal memuat riwayat</p>
                    <p>${error.message}</p>
                </div>
            `;
        }
    }

    function renderHistoryList(transactions) {
        const historyList = document.getElementById('historyList');
        if (!historyList) return;

        if (transactions.length === 0) {
            const dateFilter = document.getElementById('historyDateFilter')?.value || '';
            const emptyMsg = dateFilter
                ? `Tidak ada transaksi pada tanggal ${formatDateDisplay(dateFilter)}`
                : 'Tidak ada hasil pencarian';
            const emptyHint = dateFilter
                ? 'Coba pilih tanggal lain atau klik Reset untuk melihat semua'
                : 'Coba gunakan kata kunci yang berbeda';
            historyList.innerHTML = `
                <div class="history-empty">
                    <div class="history-empty-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <p class="history-empty-title">${emptyMsg}</p>
                    <p>${emptyHint}</p>
                </div>
            `;
            return;
        }

        historyList.innerHTML = transactions.map(txn => `
            <div class="history-item">
                <div class="history-item-header">
                    <span class="history-item-id">#${txn.transaction_code || txn.transaction_id || txn.id}</span>
                    <span class="history-item-status ${txn.status || 'completed'}">
                        ${txn.status === 'pending' ? 'Menunggu' : 'Selesai'}
                    </span>
                </div>
                <div class="history-item-meta">
                    <span>${formatDateTime(txn.created_at)}</span>
                    <span>${txn.payment_method ? txn.payment_method.toUpperCase() : 'CASH'}</span>
                    <span>Kasir: ${txn.cashier_name || txn.served_by || '-'}</span>
                    ${txn.waiter_name && txn.waiter_name !== '-' ? `<span>Pelayan: ${txn.waiter_name}</span>` : ''}
                </div>
                <div class="history-item-customer">
                    <strong>Pembeli:</strong> ${escapeHtml(txn.customer_name || 'Pelanggan Umum')}
                </div>
                ${txn.notes ? `<div class="history-item-notes">${escapeHtml(txn.notes)}</div>` : ''}
                <div class="history-item-amount">
                    Rp${Number(txn.total_amount || 0).toLocaleString('id-ID')}
                </div>
                <div class="history-item-actions">
                    <button class="btn-detail" onclick="viewTransactionDetail('${txn.transaction_id || txn.id}')">Detail</button>
                    ${historyIsWaiter ? '' : `<button class="btn-print" onclick="printReceipt('${txn.receipt_url || '#'}')">Cetak Struk</button>`}
                </div>
            </div>
        `).join('');
    }

    function applyHistoryFilter() {
        const searchTerm = (document.getElementById('historySearchInput')?.value || '').toLowerCase().trim();
        const dateFilter = document.getElementById('historyDateFilter')?.value || '';

        filteredTransactions = allTransactions.filter(txn => {
            // --- text search ---
            if (searchTerm) {
                const id       = String(txn.transaction_id || txn.id || txn.transaction_code || '').toLowerCase();
                const cashier  = String(txn.cashier_name || txn.served_by || '').toLowerCase();
                const waiter   = String(txn.waiter_name || '').toLowerCase();
                const method   = String(txn.payment_method || '').toLowerCase();
                const notes    = String(txn.notes || '').toLowerCase();
                const customer = String(txn.customer_name || '').toLowerCase();

                const textMatch = id.includes(searchTerm) ||
                                  cashier.includes(searchTerm) ||
                                  waiter.includes(searchTerm) ||
                                  method.includes(searchTerm) ||
                                  notes.includes(searchTerm) ||
                                  customer.includes(searchTerm);
                if (!textMatch) return false;
            }

            // --- filter tanggal (exact date match, timezone-aware) ---
            if (dateFilter) {
                // Konversi created_at (UTC) ke tanggal lokal user (YYYY-MM-DD)
                const txnLocalDate = txn.created_at
                    ? new Date(txn.created_at).toLocaleDateString('sv-SE') // sv-SE selalu YYYY-MM-DD
                    : '';
                if (txnLocalDate !== dateFilter) return false;
            }

            return true;
        });

        renderHistoryList(filteredTransactions);
    }

    // Backward-compat alias (dipakai jika ada kode lama yang masih memanggil searchHistory)
    function searchHistory(query) {
        if (document.getElementById('historySearchInput')) {
            document.getElementById('historySearchInput').value = query;
        }
        applyHistoryFilter();
    }

    function resetHistoryDateFilter() {
        const el = document.getElementById('historyDateFilter');
        if (el) el.value = '';
        applyHistoryFilter();
    }

    function formatDateDisplay(dateStr) {
        try {
            // dateStr adalah YYYY-MM-DD dari input[type=date]
            const [y, m, d] = dateStr.split('-');
            return `${d}/${m}/${y}`;
        } catch (e) {
            return dateStr;
        }
    }

    function formatDateTime(dateStr) {
        try {
            const date = new Date(dateStr);
            return date.toLocaleString('id-ID', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch (e) {
            return dateStr;
        }
    }

    function printReceipt(receiptUrl) {
        console.log('printReceipt called with:', receiptUrl);
        if (!receiptUrl || receiptUrl === '#') {
            window.showAlert('Struk tidak tersedia untuk transaksi ini.', 'warning');
            return;
        }

        if (typeof window.askPrintReceipt === 'function') {
            console.log('Calling window.askPrintReceipt...');
            window.askPrintReceipt(receiptUrl);
        } else {
            console.log('window.askPrintReceipt not found, falling back to window.open');
            window.open(receiptUrl, '_blank', 'width=420,height=760');
        }
    }

    function viewTransactionDetail(transactionId) {
        const txn = allTransactions.find(t => String(t.transaction_id || t.id) === String(transactionId));
        if (!txn) return;

        let detail = `
            <div style="font-size: 13px; color: var(--dark); line-height: 1.5; display: grid; grid-template-columns: 1fr 1fr; gap: 8px 16px; margin-bottom: 16px;">
                <div><strong>ID Transaksi:</strong> #${txn.transaction_code || txn.transaction_id || txn.id}</div>
                <div><strong>Tanggal:</strong> ${formatDateTime(txn.created_at)}</div>
                <div><strong>Metode Pembayaran:</strong> ${txn.payment_method ? txn.payment_method.toUpperCase() : 'CASH'}</div>
                <div><strong>Kasir:</strong> ${escapeHtml(txn.cashier_name || txn.served_by || '-')}</div>
                <div><strong>Pelayan:</strong> ${escapeHtml(txn.waiter_name || '-')}</div>
                <div><strong>Pembeli:</strong> ${escapeHtml(txn.customer_name || 'Pelanggan Umum')}</div>
                <div><strong>Status:</strong> ${txn.status === 'pending' ? 'Menunggu' : 'Selesai'}</div>
                <div style="grid-column: 1 / -1;"><strong>Catatan/Notes:</strong> ${escapeHtml(txn.notes || '-')}</div>
            </div>
        `;

        let itemsHtml = `
            <div style="border-top: 1px dashed #cbd5e1; padding-top: 12px;">
                <h4 style="font-weight: 700; font-size: 13px; color: var(--dark); margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.04em;">Daftar Item Belanja</h4>
                <table style="width: 100%; border-collapse: collapse; font-size: 12px; text-align: left;">
                    <thead>
                        <tr style="border-bottom: 2px solid #cbd5e1; color: #475569;">
                            <th style="padding: 6px 4px;">Nama Barang</th>
                            <th style="padding: 6px 4px; text-align: center; width: 60px;">Qty</th>
                            <th style="padding: 6px 4px; text-align: right; width: 100px;">Harga Satuan</th>
                            <th style="padding: 6px 4px; text-align: right; width: 110px;">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        if (txn.items && txn.items.length > 0) {
            txn.items.forEach(item => {
                itemsHtml += `
                    <tr style="border-bottom: 1px solid #f1f5f9; color: var(--dark);">
                        <td style="padding: 6px 4px; font-weight: 600;">${escapeHtml(item.product_name)}${item.variant ? ' (' + escapeHtml(item.variant) + ')' : ''}</td>
                        <td style="padding: 6px 4px; text-align: center;">${item.quantity}</td>
                        <td style="padding: 6px 4px; text-align: right;">Rp${Number(item.unit_price).toLocaleString('id-ID')}</td>
                        <td style="padding: 6px 4px; text-align: right; font-weight: 600;">Rp${Number(item.subtotal).toLocaleString('id-ID')}</td>
                    </tr>
                `;
            });
        } else {
            itemsHtml += `
                <tr>
                    <td colspan="4" style="padding: 12px 4px; text-align: center; color: #94a3b8;">Tidak ada data barang.</td>
                </tr>
            `;
        }

        itemsHtml += `
                    </tbody>
                    <tfoot>
                        <tr style="font-weight: 700; font-size: 13px; color: var(--accent); border-top: 2px solid #cbd5e1;">
                            <td colspan="3" style="padding: 10px 4px; text-align: left;">TOTAL BELANJA</td>
                            <td style="padding: 10px 4px; text-align: right;">Rp${Number(txn.total_amount || 0).toLocaleString('id-ID')}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        `;

        if (typeof openAppModal === 'function') {
            openAppModal({
                size: 'modal-md',
                title: 'Detail Transaksi',
                message: detail + itemsHtml,
                okText: 'Tutup',
                showCancel: false
            });
        } else {
            window.showAlert(detail + itemsHtml, 'info');
        }
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    // Load history ketika halaman history dibuka
    document.addEventListener('DOMContentLoaded', function() {
        // Trigger load history ketika showPage('history') dipanggil
    });
</script>
