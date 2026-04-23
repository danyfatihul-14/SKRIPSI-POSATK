<script>
    window.pendingTransactionMap = window.pendingTransactionMap || {};
    window.selectedPendingTransaction = window.selectedPendingTransaction || null;

    function renderPendingList() {
        const box = document.getElementById('pendingList');
        if (!box) return;

        fetch('{{ route("kasir.pending.list") }}')
            .then(r => r.json())
            .then(rows => {
                window.pendingTransactionMap = {};

                const pendingBadge = document.getElementById('pendingBadge');
                const pendingBadgeSidebar = document.getElementById('pendingBadgeSidebar');
                const pendingTotalCount = document.getElementById('pendingTotalCount');
                const totalPending = Array.isArray(rows) ? rows.length : 0;

                if (pendingTotalCount) pendingTotalCount.innerText = totalPending;
                if (pendingBadge) {
                    pendingBadge.innerText = totalPending;
                    pendingBadge.style.display = totalPending === 0 ? 'none' : 'flex';
                }
                if (pendingBadgeSidebar) {
                    pendingBadgeSidebar.innerText = totalPending;
                    pendingBadgeSidebar.style.display = totalPending === 0 ? 'none' : 'inline-block';
                }

                if (!rows.length) {
                    box.innerHTML = `
                    <div class="pending-empty">
                        Belum ada transaksi pending.
                    </div>
                `;
                    return;
                }

                rows.forEach(row => {
                    window.pendingTransactionMap[row.transaction_id] = row;
                });

                box.innerHTML = rows.map(row => `
                <div style="background:#fff; border:1px solid #e8ecf3; border-radius:12px; padding:12px 14px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
                        <div>
                            <div style="font-weight:700; color:#0f172a;">${row.transaction_code}</div>
                            <div style="font-size:12px; color:#64748b; margin-top:2px;">
                                Pelayan: ${row.served_by} | ${row.created_at}
                            </div>
                            <div style="margin-top:6px; font-weight:700; color:var(--accent);">
                                Rp${Number(row.total_amount).toLocaleString('id-ID')}
                            </div>
                        </div>

                        <div style="display:flex; gap:8px;">
                            <button type="button" onclick="openPendingPayment(${row.transaction_id})"
                                style="padding:8px 12px; border:none; border-radius:9px; background:var(--accent); color:#fff; cursor:pointer; font-size:12px; font-weight:700;">
                                Bayar
                            </button>
                            <button type="button" onclick="deletePendingPayment(${row.transaction_id})"
                                style="padding:8px 12px; border:1px solid #fecaca; border-radius:9px; background:#fff5f5; color:#dc2626; cursor:pointer; font-size:12px; font-weight:700;">
                                Hapus
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
            })
            .catch(() => {
                box.innerHTML = '<div class="pending-empty" style="color:#b91c1c;">Gagal memuat data pending.</div>';
            });
    }

    function openPendingPayment(id) {
        const trx = window.pendingTransactionMap?.[id];

        if (!trx) {
            window.showAlert('Data transaksi pending tidak ditemukan.', 'warning');
            return;
        }

        window.selectedPendingTransaction = trx;
        showPage('transaksi');
    }

    function deletePendingPayment(id) {
        window.showConfirm(
            'Yakin hapus transaksi pending ini?',
            () => {
                fetch(`/kasir/pending/${id}`, {
                        method: 'DELETE',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        },
                    })
                    .then(async (r) => {
                        const data = await r.json();
                        if (!r.ok) throw new Error(data.message || 'Gagal menghapus transaksi.');
                        if (window.selectedPendingTransaction?.transaction_id === id) {
                            window.selectedPendingTransaction = null;
                        }
                        window.showAlert(data.message || 'Transaksi berhasil dihapus.', 'success');
                        renderPendingList();
                    })
                    .catch((e) => window.showAlert(e.message, 'error'));
            });
    }


    function closeAppModal() {
        const el = document.getElementById('appModal');
        if (!el) return;
        el.classList.add('is-hidden');
        el.setAttribute('aria-hidden', 'true');
    }

    function openAppModal(opts = {}) {
        const config = Object.assign({
            size: 'modal-sm', // modal-sm | modal-xl
            title: 'Informasi',
            message: '',
            okText: 'OK',
            cancelText: 'Batal',
            showCancel: false,
            onOk: null,
            onCancel: null
        }, opts);

        const modal = document.getElementById('appModal');
        const dialog = document.getElementById('appModalDialog');
        const title = document.getElementById('appModalTitle');
        const body = document.getElementById('appModalBody');
        const ok = document.getElementById('appModalOk');
        const cancel = document.getElementById('appModalCancel');
        if (!modal || !dialog || !title || !body || !ok || !cancel) return;

        dialog.classList.remove('modal-sm', 'modal-xl');
        dialog.classList.add(config.size === 'modal-xl' ? 'modal-xl' : 'modal-sm');

        title.textContent = config.title;
        body.innerHTML = config.message;
        ok.textContent = config.okText;
        cancel.textContent = config.cancelText;
        cancel.style.display = config.showCancel ? 'inline-block' : 'none';

        ok.onclick = () => {
            closeAppModal();
            if (typeof config.onOk === 'function') config.onOk();
        };
        cancel.onclick = () => {
            closeAppModal();
            if (typeof config.onCancel === 'function') config.onCancel();
        };

        modal.classList.remove('is-hidden');
        modal.setAttribute('aria-hidden', 'false');
    }

    window.showAlert = function(message, type = 'info') {
        const titleMap = {
            success: 'Berhasil',
            error: 'Error',
            warning: 'Peringatan',
            info: 'Informasi'
        };
        openAppModal({
            size: 'modal-sm',
            title: titleMap[type] || 'Informasi',
            message: message || '-',
            okText: 'OK',
            showCancel: false
        });
    };

    window.showConfirm = function(message, onOk, onCancel = null, size = 'modal-sm') {
        openAppModal({
            size,
            title: 'Konfirmasi',
            message,
            okText: 'Ya',
            cancelText: 'Batal',
            showCancel: true,
            onOk,
            onCancel
        });
    };
</script>