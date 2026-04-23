<script>
    let cart = [];
    let products = [];
    let searchTimer = null;
    let receiptPrintUrl = '';

    function fetchProducts(keyword = '') {
        fetch('/kasir/search?q=' + encodeURIComponent(keyword))
            .then(r => r.json())
            .then(data => {
                products = data;
                renderProducts(data);
            });
    }

    function setupProductSearch() {
        const input = document.getElementById('productSearch');
        if (!input) return;

        input.addEventListener('input', function() {
            const keyword = this.value || '';
            clearTimeout(searchTimer);

            searchTimer = setTimeout(() => {
                fetchProducts(keyword);
            }, 250);
        });
    }

    function noImageHTML(name) {
        const colors = ['#E8F5E9', '#E3F2FD', '#FFF3E0', '#FCE4EC', '#F3E5F5'];
        const textColors = ['#2E7D32', '#1565C0', '#E65100', '#C2185B', '#6A1B9A'];
        const idx = name ? name.charCodeAt(0) % colors.length : 0;
        const initials = name ? name.charAt(0).toUpperCase() : '?';
        return `<div style="width: 100%; height: 100%; background: ${colors[idx]}; display: flex; flex-direction: column; align-items: center; justify-content: center; position: absolute; inset: 0;">
            <div style="width: 56px; height: 56px; border-radius: 12px; background: white; box-shadow: 0 2px 6px rgba(0,0,0,.1); display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: 700; color: ${textColors[idx]}; margin-bottom: 8px;">${initials}</div>
            <span style="font-size: 11px; color: #999; font-weight: 500;">No Image</span>
        </div>`;
    }

    function renderProducts(list) {
        const box = document.getElementById('productList');
        const countEl = document.getElementById('productCount');
        if (!box) return;
        if (countEl) countEl.innerText = list.length;

        if (!list.length) {
            box.innerHTML = `<div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px;">
                <div style="width: 80px; height: 80px; background: #f0f0f0; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px;">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width: 40px; height: 40px; color: #ccc;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <div style="color: var(--dark); font-weight: 600; font-size: 16px;">Produk tidak ditemukan</div>
                <div style="color: var(--dark-light); font-size: 13px; margin-top: 8px;">Coba kata kunci yang berbeda</div>
            </div>`;
            return;
        }

        box.innerHTML = list.map(prod => `
            <div class="product-card"
                onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,.12)'"
                onmouseout="this.style.boxShadow='0 1px 3px rgba(0,0,0,.08)'"
                onclick="addCart('${prod.product_id}','${prod.product_name.replace(/'/g,"\\'")}',${prod.selling_price})">

                <div style="position: relative; width: 100%; aspect-ratio: 1; overflow: hidden; background: #f5f5f5;">
                    ${prod.image_url
                        ? `<img src="${prod.image_url}" style="width: 100%; height: 100%; object-fit: cover; transition: transform .3s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'" onerror="this.parentElement.innerHTML=noImageHTML('${prod.product_name.charAt(0)}')">`
                        : `<div style="width: 100%; height: 100%;">${noImageHTML(prod.product_name)}</div>`}

                    ${prod.discount ? `
                    <div style="position: absolute; top: 8px; left: 8px;">
                        <span style="background: #ef4444; color: white; font-size: 11px; font-weight: 700; padding: 4px 8px; border-radius: 6px;">${prod.discount}%</span>
                    </div>` : ''}
                </div>

                <div class="product-info">
                    <div style="font-size:13px; font-weight:600; color:var(--dark); margin-bottom:6px; line-height:1.3; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">
                        ${prod.product_name}
                    </div>
                    <div style="color:var(--accent); font-weight:700; font-size:14px; margin-bottom:10px;">
                        Rp${parseInt(prod.selling_price).toLocaleString('id-ID')}
                    </div>
                    <div style="font-size:13px; color:var(--accent); margin-bottom:8px; font-weight:600;">
                        Satuan: ${prod.unit}
                    </div>
                </div>
            </div>
        `).join('');
    }

    function addCart(id, name, price, btn = null) {
        let idx = cart.findIndex(i => i.id == id);
        if (idx >= 0) cart[idx].qty++;
        else cart.push({
            id,
            name,
            price: parseFloat(price),
            qty: 1
        });

        updateCartBadge();

        if (!btn) return; // jika klik kartu, lewati animasi tombol

        const orig = btn.innerHTML;
        btn.innerHTML = '✅ Ditambahkan';
        btn.disabled = true;
        btn.style.opacity = '0.7';
        setTimeout(() => {
            btn.innerHTML = orig;
            btn.disabled = false;
            btn.style.opacity = '1';
        }, 800);
    }

    function buyNow(id, name, price, btn) {
        addCart(id, name, price, btn);

        setTimeout(() => {
            if ("{{ auth()->user()->role }}" === 'pelayan') {
                showPage('keranjang');
                return;
            }

            showPage('transaksi');
        }, 900);
    }

    function updateCartBadge() {
        const total = cart.reduce((s, i) => s + i.qty, 0);
        const badge = document.getElementById('cartBadge');
        const badgeSidebar = document.getElementById('cartBadgeSidebar');
        const count = document.getElementById('cartCountKasir');
        const countBar = document.getElementById('cartCountBar');

        if (badge) {
            badge.innerText = total;
            badge.style.display = total === 0 ? 'none' : 'flex';
        }
        if (badgeSidebar) {
            badgeSidebar.innerText = total;
            badgeSidebar.style.display = total === 0 ? 'none' : 'inline-block';
        }
        if (count) count.innerText = total;
        if (countBar) countBar.innerText = total;

        renderQuickCartPanel();
    }

    function renderQuickCartPanel() {
        const empty = document.getElementById('quickCartEmpty');
        const list = document.getElementById('quickCartList');
        const totalEl = document.getElementById('quickCartTotal');
        const countEl = document.getElementById('quickCartCount');

        if (!empty || !list || !totalEl || !countEl) return;

        const totalQty = cart.reduce((s, i) => s + i.qty, 0);
        const totalPrice = cart.reduce((s, i) => s + (i.qty * i.price), 0);

        countEl.innerText = `${totalQty} item`;
        totalEl.innerText = 'Rp' + totalPrice.toLocaleString('id-ID');

        if (!cart.length) {
            empty.style.display = 'block';
            list.style.display = 'none';
            list.innerHTML = '';
            return;
        }

        empty.style.display = 'none';
        list.style.display = 'flex';

        list.innerHTML = cart.map((item, idx) => `
        <div style="width:100%; max-width:320px; border:1px solid var(--accent); border-radius:10px; padding:10px;">
            <div style="font-weight:600; color:var(--dark); font-size:13px; margin-bottom:6px;">
                ${item.name}
            </div>
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div style="font-size:12px; color:var(--dark-light);">
                    Rp${Number(item.price).toLocaleString('id-ID')}
                </div>
                <div style="display:flex; align-items:center; gap:6px;">
                    <button onclick="updateQty(${idx}, -1)" style="width:24px; height:24px; border:1px solid var(--accent); background:white; border-radius:6px; cursor:pointer;">-</button>
                    <span style="min-width:20px; text-align:center; font-weight:700;">${item.qty}</span>
                    <button onclick="updateQty(${idx}, 1)" style="width:24px; height:24px; border:1px solid var(--accent); background:white; border-radius:6px; cursor:pointer;">+</button>
                    <button onclick="removeItem(${idx})" style="margin-left:4px; border:none; background:#ef4444; color:white; border-radius:6px; padding:4px 6px; cursor:pointer;">x</button>
                </div>
            </div>
        </div>
    `).join('');
    }

    function quickHoldOrder() {
        if (!cart.length) {
            window.showAlert('Keranjang masih kosong.', 'warning');
            return;
        }

        if (typeof holdCurrentCart === 'function') {
            holdCurrentCart();
            window.showAlert('Order dipindahkan ke Hold Order.');
            return;
        }
        window.showAlert('Fitur Hold Order belum siap.', 'warning');
    }

    function quickSendToWaitingPayment() {
        if (!cart.length) {
            window.showAlert('Keranjang masih kosong.', 'warning');
            return;
        }

        if (typeof submitOrderToCashier !== 'function') {
            window.showAlert('Fitur kirim ke waiting payment belum siap.', 'warning');
            return;
        }

        submitOrderToCashier({
            redirectPage: 'pending',
            successMessage: 'Order berhasil dikirim ke Waiting Payment.',
        });
    }

    function submitOrderToCashier(options = {}) {
        if (!cart.length) {
            window.showAlert('Keranjang masih kosong.', 'warning');
            return;
        }

        const redirectPage = options.redirectPage || 'kasir';
        const successMessage = options.successMessage || 'Pesanan berhasil dikirim ke kasir.';

        // Buat form element
        const form = new FormData();
        form.append('cart', JSON.stringify(cart)); // ← Stringify!
        form.append('payment', 0); // ← Numeric
        form.append('payment_method', 'cash'); // ← String

        fetch('{{ route("kasir.order.store") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: form,
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
                }
            })
            .catch(e => window.showAlert('Error: ' + e.message, 'error'));
    }

    function showCenterMessage(message, title = 'Informasi') {
        const modal = document.getElementById('receiptModal');
        if (!modal) return;

        const titleEl = document.getElementById('receiptModalTitle');
        const msgEl = document.getElementById('receiptModalMessage');
        const printBtn = document.getElementById('receiptModalPrintBtn');

        if (titleEl) titleEl.textContent = title;
        if (msgEl) msgEl.textContent = message;
        if (printBtn) printBtn.style.display = 'none';

        modal.classList.remove('hidden');
    }

    function closeReceiptModal() {
        const modal = document.getElementById('receiptModal');
        if (modal) modal.classList.add('hidden');
    }

    document.addEventListener('click', function(e) {
        const modal = document.getElementById('receiptModal');
        if (!modal) return;
        const backdrop = modal.querySelector('.receipt-modal__backdrop');
        if (e.target === backdrop) closeReceiptModal();
    });

    function quickHoldOrder() {
        if (!cart.length) return showCenterMessage('Keranjang masih kosong.');
        if (typeof holdCurrentCart === 'function') {
            holdCurrentCart();
            return showCenterMessage('Order dipindahkan ke Hold Order.', 'Berhasil');
        }
        return window.showAlert('Fitur Hold Order belum siap.', 'warning');
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

        ok.onclick = () => { closeAppModal(); if (typeof config.onOk === 'function') config.onOk(); };
        cancel.onclick = () => { closeAppModal(); if (typeof config.onCancel === 'function') config.onCancel(); };

        modal.classList.remove('is-hidden');
        modal.setAttribute('aria-hidden', 'false');
    }

    window.showAlert = function(message, type = 'info') {
        const titleMap = { success:'Berhasil', error:'Error', warning:'Peringatan', info:'Informasi' };
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

    fetchProducts('');
    setupProductSearch();
    updateCartBadge();
</script>