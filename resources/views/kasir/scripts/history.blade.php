<script>
    const historyIsWaiter = document.querySelector('.main-container')?.dataset.isWaiter === '1';
    let allTransactions = [];
    let filteredTransactions = [];

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
            historyList.innerHTML = `
                <div class="history-empty">
                    <div class="history-empty-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <p class="history-empty-title">Tidak ada hasil pencarian</p>
                    <p>Coba gunakan kata kunci yang berbeda</p>
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
                    <span>${txn.served_by || '-'}</span>
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

    function searchHistory(query) {
        const searchTerm = query.toLowerCase().trim();

        if (!searchTerm) {
            filteredTransactions = [...allTransactions];
        } else {
            filteredTransactions = allTransactions.filter(txn => {
                const id = String(txn.transaction_id || txn.id || txn.transaction_code || '').toLowerCase();
                const cashier = String(txn.served_by || '').toLowerCase();
                const method = String(txn.payment_method || '').toLowerCase();
                const notes = String(txn.notes || '').toLowerCase();
                const customer = String(txn.customer_name || '').toLowerCase();
                
                return id.includes(searchTerm) || 
                       cashier.includes(searchTerm) || 
                       method.includes(searchTerm) ||
                       notes.includes(searchTerm) ||
                       customer.includes(searchTerm);
            });
        }

        renderHistoryList(filteredTransactions);
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
        if (!receiptUrl || receiptUrl === '#') {
            window.showAlert('Struk tidak tersedia untuk transaksi ini.', 'warning');
            return;
        }

        if (typeof askPrintReceipt === 'function') {
            askPrintReceipt(receiptUrl);
        } else {
            window.open(receiptUrl, '_blank', 'width=420,height=760');
        }
    }

    function viewTransactionDetail(transactionId) {
        const txn = allTransactions.find(t => String(t.transaction_id || t.id) === String(transactionId));
        if (!txn) return;

        const detail = `
            <strong>ID Transaksi:</strong> #${txn.transaction_code || txn.transaction_id || txn.id}<br>
            <strong>Tanggal:</strong> ${formatDateTime(txn.created_at)}<br>
            <strong>Metode Pembayaran:</strong> ${txn.payment_method ? txn.payment_method.toUpperCase() : 'CASH'}<br>
            <strong>Total:</strong> Rp${Number(txn.total_amount || 0).toLocaleString('id-ID')}<br>
            <strong>Status:</strong> ${txn.status === 'pending' ? 'Menunggu' : 'Selesai'}<br>
            <strong>Kasir:</strong> ${txn.served_by || '-'}<br>
            <strong>Pembeli:</strong> ${escapeHtml(txn.customer_name || 'Pelanggan Umum')}<br>
            <strong>Notes:</strong> ${escapeHtml(txn.notes || '-')}
        `;

        window.showAlert(detail, 'info');
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
