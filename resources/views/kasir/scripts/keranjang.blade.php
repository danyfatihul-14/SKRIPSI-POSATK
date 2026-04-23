{{-- filepath: resources/views/kasir/scripts/keranjang.blade.php --}}
<script>
    function renderCart() {
        const empty = document.getElementById('cartEmpty');
        const list = document.getElementById('cartList');
        const footer = document.getElementById('cartFooter');
        const totalEl = document.getElementById('cartTotalKeranjang');

        if (!cart.length) {
            if (empty) empty.style.display = 'block';
            if (list) list.style.display = 'none';
            if (footer) footer.style.display = 'none';
            return;
        }

        if (empty) empty.style.display = 'none';
        if (list) {
            list.style.display = 'flex';
            list.innerHTML = cart.map((item, idx) => `
                <div style="background: white; border-radius: 12px; padding: 16px; box-shadow: 0 1px 3px rgba(0,0,0,.08); border: 1px solid var(--accent); display: flex; align-items: center; gap: 16px; transition: all .2s;" onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,.12)'" onmouseout="this.style.boxShadow='0 1px 3px rgba(0,0,0,.08)'">
                    
                    {{-- Product Info --}}
                    <div style="flex: 1; min-width: 0;">
                        <div style="font-weight: 600; font-size: 15px; color: var(--dark); margin-bottom: 6px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${item.name}</div>
                        <div style="color: var(--accent); font-weight: 700; font-size: 16px;">Rp${parseInt(item.price).toLocaleString('id-ID')}</div>
                    </div>

                    {{-- Quantity Controls --}}
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <button onclick="updateQty(${idx}, -1)" 
                            style="width: 32px; height: 32px; background: var(--light); border: 1px solid var(--accent); border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all .2s; font-weight: 700; color: var(--dark); font-size: 16px; font-family: inherit;"
                            onmouseover="this.style.backgroundColor='var(--accent)'" 
                            onmouseout="this.style.backgroundColor='var(--light)'">
                            -
                        </button>
                        <div style="width: 48px; text-align: center; font-weight: 700; font-size: 16px; color: var(--dark);">${item.qty}</div>
                        <button onclick="updateQty(${idx}, 1)" 
                            style="width: 32px; height: 32px; background: var(--light); border: 1px solid var(--accent); border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all .2s; font-weight: 700; color: var(--dark); font-size: 16px; font-family: inherit;"
                            onmouseover="this.style.backgroundColor='var(--accent)'" 
                            onmouseout="this.style.backgroundColor='var(--light)'">
                            +
                        </button>
                    </div>

                    {{-- Subtotal --}}
                    <div style="min-width: 120px; text-align: right;">
                        <div style="font-size: 12px; color: var(--dark-light); margin-bottom: 4px;">Subtotal</div>
                        <div style="font-weight: 700; font-size: 16px; color: var(--dark);">Rp${(item.price * item.qty).toLocaleString('id-ID')}</div>
                    </div>

                    {{-- Delete Button --}}
                    <button onclick="removeItem(${idx})" 
                        style="width: 36px; height: 36px; background: transparent; border: 1px solid #ef4444; border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all .2s; flex-shrink: 0; font-family: inherit;"
                        onmouseover="this.style.backgroundColor='#fef2f2'" 
                        onmouseout="this.style.backgroundColor='transparent'">
                        <svg xmlns="http://www.w3.org/2000/svg" style="width: 18px; height: 18px; color: #ef4444;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>

                </div>
            `).join('');
        }
        if (footer) footer.style.display = 'block';

        const total = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
        if (totalEl) totalEl.innerText = 'Rp' + total.toLocaleString('id-ID');
    }

    function updateQty(idx, change) {
        if (!cart[idx]) return;
        cart[idx].qty += change;
        if (cart[idx].qty <= 0) {
            cart.splice(idx, 1);
        }
        updateCartBadge();
        renderCart();
    }

    function removeItem(idx) {
        window.showConfirm('Hapus item ini dari keranjang?', () => {
            cart.splice(idx, 1);
            updateCartBadge();
            renderCart();
        });
    }

    function submitOrderToCashier(options = {}) {
        if (!cart.length) {
            window.showAlert('Keranjang masih kosong.', 'warning');
            return;
        }

        const redirectPage = options.redirectPage || 'kasir';
        const successMessage = options.successMessage || 'Pesanan berhasil dikirim ke kasir.';

        // Encode as URL params explicit
        const params = new URLSearchParams();
        params.append('cart', JSON.stringify(cart));
        params.append('payment', 0);
        params.append('payment_method', 'cash');

        fetch('{{ route("kasir.order.store") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: params,
            })
            .then(r => r.json())
            .then(data => {
                if (data.message) {
                    cart = [];
                    updateCartBadge();
                    showPage(redirectPage);
                    if (redirectPage === 'pending' && typeof renderPendingList === 'function') {
                        setTimeout(() => renderPendingList(), 300);
                    }
                    window.showAlert(data.message);
                } else if (data.errors) {
                    window.showAlert('Error: ' + JSON.stringify(data.errors), 'error');
                }
            })
            .catch(e => window.showAlert('Fetch error: ' + e.message, 'error'));
    }

    let heldCarts = JSON.parse(localStorage.getItem('heldCarts') || '[]');

    function saveHeldCarts() {
        localStorage.setItem('heldCarts', JSON.stringify(heldCarts));
    }

    function renderQueueList() {
        const box = document.getElementById('queueList');
        const count = document.getElementById('queueCount');
        if (!box || !count) return;

        const holdBadge = document.getElementById('holdBadge');
        const holdBadgeSidebar = document.getElementById('holdBadgeSidebar');
        const totalQueue = heldCarts.length;

        if (holdBadge) {
            holdBadge.innerText = totalQueue;
            holdBadge.style.display = totalQueue === 0 ? 'none' : 'flex';
        }

        if (holdBadgeSidebar) {
            holdBadgeSidebar.innerText = totalQueue;
            holdBadgeSidebar.style.display = totalQueue === 0 ? 'none' : 'inline-block';
        }

        count.innerText = heldCarts.length;
        if (!heldCarts.length) {
            box.innerHTML = '<span style="font-size:12px; color:var(--dark-light);">Belum ada antrian.</span>';
            return;
        }

        box.innerHTML = heldCarts.map((q, idx) => `
        <div style="border:1px solid var(--accent); border-radius:8px; padding:8px 10px; background:#f9fafb;">
            <div style="font-size:12px; font-weight:700;">${q.code}</div>
            <div style="font-size:11px; color:#6b7280;">${q.totalItems} item - Rp${q.total.toLocaleString('id-ID')}</div>
            <div style="display:flex; gap:6px; margin-top:6px;">
                <button type="button" onclick="event.preventDefault(); activateHeldCart(${idx})" style="padding:4px 8px; border:none; border-radius:6px; background:#2563eb; color:white; cursor:pointer;">Aktifkan</button>
                <button type="button" onclick="deleteHeldCart(${idx})" style="padding:4px 8px; border:none; border-radius:6px; background:#ef4444; color:white; cursor:pointer;">Hapus</button>
            </div>
        </div>
    `).join('');
    }

    function holdCurrentCart() {
        if (!cart.length) {
            window.showAlert('Keranjang kosong.', 'warning');
            return;
        }

        const total = cart.reduce((s, i) => s + i.price * i.qty, 0);
        const totalItems = cart.reduce((s, i) => s + i.qty, 0);

        heldCarts.push({
            code: 'ANTRIAN-' + Date.now(),
            cart: JSON.parse(JSON.stringify(cart)),
            total,
            totalItems,
            createdAt: new Date().toISOString(),
        });

        cart = [];
        updateCartBadge();
        renderCart();
        saveHeldCarts();
        renderQueueList();
    }

    function activateHeldCart(index) {
        if (!heldCarts[index]) return;

        if (cart.length) {
            window.showConfirm(
            'Keranjang aktif belum kosong. Simpan dulu ke antrian?',
            () => {
                holdCurrentCart();
                activateHeldCart(index);
            }
            );
            return;
        }

        cart = heldCarts[index].cart;
        heldCarts.splice(index, 1);

        updateCartBadge();
        renderCart();
        saveHeldCarts();
        renderQueueList();
    }

    function deleteHeldCart(index) {
        if (!heldCarts[index]) return;
        if (!window.showConfirm('Hapus antrian ini?')) return;
        heldCarts.splice(index, 1);
        saveHeldCarts();
        renderQueueList();
    }

    function activateHeldCart(index) {
        if (!heldCarts[index]) return;

        if (cart.length) {
            const lanjut = window.showConfirm('Keranjang aktif belum kosong. Simpan dulu ke antrian?');
            if (!lanjut) return;
            holdCurrentCart();
        }

        cart = heldCarts[index].cart;
        heldCarts.splice(index, 1);

        updateCartBadge();
        renderCart();
        saveHeldCarts();
        renderQueueList();

        // pindah tampilan ke kasir
        if (typeof showPage === 'function') {
            showPage('kasir');
        }
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

    renderQueueList();
</script>