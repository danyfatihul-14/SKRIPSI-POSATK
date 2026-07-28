<script>
    window.printProductQr = function(productId, productName, variant) {
        const qrUrl = 'https://bwipjs-api.metafloor.com/?bcid=code128&text=' + productId + '&scale=1.5&rotate=N&includetext&height=8';
        const printWindow = window.open('', '_blank', 'width=450,height=400');
        printWindow.document.write('<html><head><title>Cetak Barcode - ' + productName + '</title>' +
            '<style>' +
            'body { font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; text-align: center; padding: 0; margin: 0; background: white; display: flex; align-items: center; justify-content: center; height: 100vh; }' +
            '.container { ' +
            '  width: 50mm; ' +
            '  height: 30mm; ' +
            '  border: 1px dashed #8b5cf6; ' +
            '  padding: 1.5mm; ' +
            '  display: flex; ' +
            '  flex-direction: column; ' +
            '  align-items: center; ' +
            '  justify-content: center; ' +
            '  box-sizing: border-box; ' +
            '  border-radius: 6px; ' +
            '  background: white; ' +
            '}' +
            'h3 { margin: 2px 0 0 0; font-size: 8px; color: #1e1b4b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; width: 100%; text-align: center; }' +
            'p { margin: 0; font-size: 7px; color: #6b21a8; font-weight: 600; text-align: center; }' +
            'img { width: 44mm; height: 15mm; object-fit: contain; }' +
            '</style></' + 'head><body>' +
            '<div class="container">' +
            '<img src="' + qrUrl + '" alt="Barcode" />' +
            '<h3>' + productName + '</h3>' +
            '<p>Varian: ' + variant + '</p>' +
            '</div>' +
            '<' + 'script>' +
            'window.onload = function() {' +
            '  setTimeout(function() {' +
            '    window.print();' +
            '    window.close();' +
            '  }, 600);' +
            '};' +
            '</' + 'script></' + 'body></html>');
        printWindow.document.close();
    };

    let cart = [];
    const currentUserId = "{{ auth()->user()->user_id ?? auth()->user()->id ?? 'guest' }}";
    const heldCartsKey = 'heldCarts_' + currentUserId;
    let heldCarts = JSON.parse(localStorage.getItem(heldCartsKey) || '[]');
    var products = [];
    let searchTimer = null;
    let receiptPrintUrl = '';

    function getVariantDisplayName(p) {
        const variantEmpty = !p.variant || p.variant === '-' || p.variant.toLowerCase() === 'default';
        const unitEmpty = !p.unit || p.unit === '-' || p.unit.toLowerCase() === 'default';

        if (variantEmpty && !unitEmpty) {
            return p.unit;
        }
        if (!variantEmpty && unitEmpty) {
            return p.variant;
        }
        if (!variantEmpty && !unitEmpty) {
            if (p.variant.toLowerCase() === p.unit.toLowerCase()) {
                return p.variant;
            }
            return `${p.variant} (${p.unit})`;
        }
        return 'Default';
    }

    function getCartQty(productId) {
        const item = cart.find(i => i.id == productId);
        return item ? item.qty : 0;
    }

    function getHeldQty(productId) {
        let total = 0;
        heldCarts.forEach(c => {
            if (c.cart) {
                const item = c.cart.find(i => i.id == productId);
                if (item) {
                    total += item.qty;
                }
            }
        });
        return total;
    }

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

        // Group products by name
        const grouped = {};
        list.forEach(prod => {
            const name = prod.product_name;
            if (!grouped[name]) {
                grouped[name] = [];
            }
            grouped[name].push(prod);
        });

        // Sort variants within each group by price ascending
        Object.keys(grouped).forEach(name => {
            grouped[name].sort((a, b) => parseFloat(a.selling_price || 0) - parseFloat(b.selling_price || 0));
        });

        const groupedList = Object.values(grouped);
        if (countEl) countEl.innerText = groupedList.length;

        if (!groupedList.length) {
            box.innerHTML = `
                <div style="grid-column: 1 / -1; display: flex; flex-direction: column; align-items: center; justify-content: center; min-height: 300px; text-align: center;">
                    <div style="width: 72px; height: 72px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 16px;">
                        <svg xmlns="http://www.w3.org/2000/svg" style="width: 32px; height: 32px; color: #94a3b8;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                    </div>
                    <div style="font-size: 15px; font-weight: 700; color: #334155; margin-bottom: 6px;">Barang Kosong</div>
                    <div style="font-size: 13px; color: #64748b; max-width: 250px;">Belum ada barang di toko ini atau kata kunci tidak ditemukan.</div>
                </div>
            `;
            return;
        }

        box.innerHTML = groupedList.map(group => {
            if (group.length === 1) {
                const prod = group[0];
                const vName = getVariantDisplayName(prod);
                const displayName = vName !== 'Default' 
                    ? `${prod.product_name} (${vName})` 
                    : prod.product_name;
                
                const availableStock = Math.max(0, (prod.stock ?? 0) - getCartQty(prod.product_id) - getHeldQty(prod.product_id));
                const isOutOfStock = availableStock <= 0;

                return `
                <div class="product-card"
                    style="${isOutOfStock ? 'opacity: 0.55; cursor: not-allowed; position: relative;' : ''}"
                    onclick="${isOutOfStock ? '' : `addCart('${prod.product_id}','${displayName.replace(/'/g,"\\'")}',${prod.selling_price})`}">

                    <div style="position: relative; width: 100%; aspect-ratio: 1; overflow: hidden; background: #f5f5f5;">
                        ${prod.image_url
                            ? `<img src="${prod.image_url}" style="width:100%;height:100%;object-fit:cover;" onerror="this.style.display='none'">`
                            : `<div style="width:100%;height:100%;">${noImageHTML(prod.product_name)}</div>`}

                        <div style="position:absolute; top:8px; right:8px; background:${isOutOfStock ? '#ef4444' : '#111827'}; color:white; font-size:11px; padding:4px 8px; border-radius:6px; font-weight: 600;">
                            Stok: ${availableStock}
                        </div>

                        ${prod.discount ? `
                        <div style="position:absolute; top:8px; left:8px;">
                            <span style="background:#ef4444; color:white; font-size:11px; font-weight:700; padding:4px 8px; border-radius:6px;">${prod.discount}%</span>
                        </div>` : ''}
                    </div>

                    <div class="product-info">
                        <div style="font-size:13px; font-weight:600; margin-bottom:4px;">
                            ${prod.product_name}
                        </div>

                        <div style="font-size:12px; color:#2563eb; font-weight:600; margin-bottom:4px;">
                            Varian: ${getVariantDisplayName(prod) !== 'Default' ? getVariantDisplayName(prod) : '-'}
                        </div>

                        <div style="font-size:12px; color:var(--accent); margin-bottom:6px;">
                            Satuan: ${prod.unit}
                        </div>

                        <div style="font-size:14px; font-weight:700; color:var(--accent); display: flex; justify-content: space-between; align-items: center;">
                            <span>Rp${parseInt(prod.selling_price).toLocaleString('id-ID')}</span>
                            <button onclick="event.stopPropagation(); printProductQr('${prod.product_id}', '${prod.product_name.replace(/'/g,"\\'")}', '${(prod.variant || '-').replace(/'/g,"\\'")}')" style="background: #8b5cf6; color: white; border: none; padding: 4px 8px; border-radius: 4px; font-size: 10px; font-weight: bold; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 12px; height: 12px;"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 15h.008v.008H15V15zm0 2.25h.008v.008H15v-.008zM17.25 15h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zM15 19.5h.008v.008H15V19.5zm0-2.25h.008v.008H15v-.008zm2.25 0h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V19.5z" /></svg>
                                QR
                            </button>
                        </div>
                    </div>
                </div>`;
            } else {
                const firstProd = group[0];
                const totalAvailableStock = group.reduce((s, p) => s + Math.max(0, (p.stock ?? 0) - getCartQty(p.product_id) - getHeldQty(p.product_id)), 0);
                const isOutOfStock = totalAvailableStock <= 0;
                
                const prices = group.map(p => parseFloat(p.selling_price));
                const minPrice = Math.min(...prices);
                const maxPrice = Math.max(...prices);
                const priceText = minPrice === maxPrice 
                    ? `Rp${minPrice.toLocaleString('id-ID')}` 
                    : `Rp${minPrice.toLocaleString('id-ID')} - Rp${maxPrice.toLocaleString('id-ID')}`;
                
                const variantNames = group.map(p => getVariantDisplayName(p)).join(', ');
                const escapedName = firstProd.product_name.replace(/'/g, "\\'");

                return `
                <div class="product-card"
                    style="${isOutOfStock ? 'opacity: 0.55; cursor: not-allowed;' : ''}"
                    onclick="${isOutOfStock ? '' : `openVariantModal('${escapedName}')`}">

                    <div style="position: relative; width: 100%; aspect-ratio: 1; overflow: hidden; background: #f5f5f5;">
                        ${firstProd.image_url
                            ? `<img src="${firstProd.image_url}" style="width:100%;height:100%;object-fit:cover;" onerror="this.style.display='none'">`
                            : `<div style="width:100%;height:100%;">${noImageHTML(firstProd.product_name)}</div>`}

                        <div style="position:absolute; top:8px; right:8px; background:${isOutOfStock ? '#ef4444' : '#15173D'}; color:white; font-size:11px; padding:4px 8px; border-radius:6px; font-weight:600;">
                            Total Stok: ${totalAvailableStock}
                        </div>

                        <div style="position:absolute; bottom:8px; left:8px; background:#e0f2fe; color:#0369a1; font-size:10px; font-weight:700; padding:2px 6px; border-radius:4px; border:1px solid #bae6fd;">
                            ${group.length} Varian
                        </div>
                    </div>

                    <div class="product-info">
                        <div style="font-size:13px; font-weight:600; margin-bottom:4px;">
                            ${firstProd.product_name}
                        </div>

                        <div style="font-size:11px; color:#2563eb; font-weight:600; margin-bottom:4px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="${variantNames}">
                            Varian: ${variantNames}
                        </div>

                        <div style="font-size:12px; color:var(--accent); margin-bottom:6px;">
                            Satuan: ${firstProd.unit}
                        </div>

                        <div style="font-size:14px; font-weight:700; color:var(--accent);">
                            ${priceText}
                        </div>
                    </div>
                </div>`;
            }
        }).join('');
    }

    function isDynamicPriceProduct(prod) {
        return prod && !!prod.is_dynamic_price;
    }

    function addCart(id, name, price, btn = null) {
        const prod = products.find(p => p.product_id == id);
        if (!prod) return;

        const availableStock = Math.max(0, (prod.stock ?? 0) - getCartQty(id) - getHeldQty(id));
        if (availableStock <= 0) {
            window.showAlert('Stok tidak mencukupi!', 'warning');
            return;
        }

        const isDynamic = isDynamicPriceProduct(prod);

        if (isDynamic) {
            const promptTitle = 'Input Harga Jual';
            const promptMsg = `Masukkan harga jual untuk <strong>${prod.product_name}</strong>:`;
            const inputId = 'promptPriceInput_' + Date.now();
            const defaultPrice = Math.round(price || prod.selling_price) || 0;
            
            const content = `
                <div style="text-align: left; margin-bottom: 12px; font-size: 14px; color: var(--dark);">${promptMsg}</div>
                <input type="number" id="${inputId}" class="form-control" placeholder="Contoh: 15000" value="${defaultPrice}" style="width: 100%; padding: 10px 12px; border: 2px solid var(--accent); border-radius: 8px; box-sizing: border-box; font-size: 15px; font-weight: 700; color: var(--dark);" min="0">
            `;
            
            openAppModal({
                size: 'modal-sm',
                title: promptTitle,
                message: content,
                okText: 'Simpan',
                cancelText: 'Batal',
                showCancel: true,
                onOk: () => {
                    const priceVal = parseFloat(document.getElementById(inputId).value) || 0;
                    if (priceVal <= 0) {
                        window.showAlert('Harga jual tidak boleh kosong atau kurang dari sama dengan 0.', 'warning');
                        return;
                    }
                    executeAddCart(id, name, priceVal, btn);
                }
            });

            setTimeout(() => {
                const inp = document.getElementById(inputId);
                if (inp) {
                    inp.focus();
                    inp.select();
                }
            }, 100);
            return;
        }

        executeAddCart(id, name, price, btn);
    }

    function executeAddCart(id, name, price, btn = null) {
        const prod = products.find(p => p.product_id == id);
        if (!prod) return;

        let idx = cart.findIndex(i => i.id == id && i.price == parseFloat(price));
        if (idx >= 0) {
            cart[idx].qty++;
        } else {
            cart.push({
                id,
                name,
                price: parseFloat(price),
                qty: 1,
                maxStock: parseInt(prod.stock ?? 0)
            });
        }

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
        renderProducts(products);
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
        const customerNameInput = document.getElementById('quickCustomerName');
        const customerName = customerNameInput?.value?.trim() || 'Pelanggan Umum';

        console.log('📝 Customer Name:', customerName);

        const params = new URLSearchParams();
        params.append('cart', JSON.stringify(cart));
        params.append('payment', 0);
        params.append('payment_method', 'cash');
        params.append('customer_name', customerName);

        fetch('{{ route("kasir.order.store") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: params,
            })
            .then(async (r) => {
                const text = await r.text();
                let data = {};
                try {
                    data = JSON.parse(text);
                } catch (_) {}

                if (!r.ok) {
                    throw new Error(data.message || `HTTP ${r.status} - ${text.slice(0, 180)}`);
                }
                return data;
            })
            .then((data) => {
                cart = [];
                updateCartBadge();
                if (typeof fetchProducts === 'function') {
                    const searchInput = document.getElementById('productSearch');
                    fetchProducts(searchInput ? searchInput.value : '');
                }
                showPage(redirectPage);
                window.showAlert(data.message || 'Order berhasil dikirim.');
            })
            .catch((e) => window.showAlert('Error: ' + e.message, 'error'));
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

        const iframeContainer = document.getElementById('receiptIframeContainer');
        if (iframeContainer) iframeContainer.style.display = 'none';

        modal.classList.remove('hidden');
    }



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

        dialog.classList.remove('modal-sm', 'modal-xl', 'modal-md');
        if (config.size === 'modal-xl') dialog.classList.add('modal-xl');
        else if (config.size === 'modal-md') dialog.classList.add('modal-md');
        else dialog.classList.add('modal-sm');

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

    // === LOGIC MODAL VARIANT ===
    let currentVariants = [];
    let selectedVariantId = null;

    window.openVariantModal = function(productName) {
        currentVariants = products.filter(p => p.product_name === productName)
                                  .sort((a, b) => parseFloat(a.selling_price || 0) - parseFloat(b.selling_price || 0));
        if (currentVariants.length === 0) return;

        // Select the first variant that has available stock > 0, otherwise select the first variant
        const firstAvailable = currentVariants.find(v => ((v.stock ?? 0) - getCartQty(v.product_id) - getHeldQty(v.product_id)) > 0);
        if (firstAvailable) {
            selectedVariantId = firstAvailable.product_id;
        } else {
            selectedVariantId = currentVariants[0].product_id;
        }

        const titleEl = document.getElementById('variantModalTitle');
        if (titleEl) titleEl.innerText = productName;

        renderVariantOptions();

        const qtyInput = document.getElementById('variantQtyInput');
        if (qtyInput) qtyInput.value = 1;

        const modal = document.getElementById('variantModal');
        if (modal) modal.classList.remove('hidden');
    }

    function renderVariantOptions() {
        const container = document.getElementById('variantOptionsContainer');
        if (!container) return;

        container.innerHTML = currentVariants.map(v => {
            const isSelected = v.product_id == selectedVariantId;
            const borderStyle = isSelected ? 'border: 2px solid var(--accent); background: #f0f4ff;' : 'border: 1px solid #e2e8f0; background: #fff;';
            const badgeStyle = isSelected ? 'display: flex;' : 'display: none;';
            const price = parseFloat(v.selling_price).toLocaleString('id-ID');
            const variantName = getVariantDisplayName(v);
            const availableStock = Math.max(0, (v.stock ?? 0) - getCartQty(v.product_id) - getHeldQty(v.product_id));
            const isOutOfStock = availableStock <= 0;
            const cardOpacity = isOutOfStock ? 'opacity: 0.55;' : '';
            const cardCursor = isOutOfStock ? 'cursor: not-allowed;' : 'cursor: pointer;';

            return `
            <div class="variant-option-card" style="padding: 12px; border-radius: 8px; transition: all 0.2s; position: relative; ${borderStyle} ${cardOpacity} ${cardCursor}" onclick="${isOutOfStock ? '' : `selectVariant('${v.product_id}')`}">
                <div style="position: absolute; top: -6px; right: -6px; width: 16px; height: 16px; border-radius: 50%; background: var(--accent); color: white; display: ${badgeStyle === 'display: flex;' ? 'flex' : 'none'}; align-items: center; justify-content: center; font-size: 10px;">✓</div>
                <div style="font-weight: 700; font-size: 13px; color: var(--dark);">${variantName}</div>
                <div style="font-size: 12px; color: var(--dark-light); margin-top: 4px;">Rp${price}</div>
                <div style="font-size: 11px; color: ${isOutOfStock ? '#ef4444' : '#64748b'}; margin-top: 2px; font-weight: ${isOutOfStock ? '700' : 'normal'};">
                    Stok: ${availableStock} ${isOutOfStock ? '(Habis)' : ''}
                </div>
                <button type="button" onclick="event.stopPropagation(); printProductQr('${v.product_id}', '${v.product_name.replace(/'/g,"\\'")}', '${(v.variant || '-').replace(/'/g,"\\'")}')" style="position: absolute; bottom: 8px; right: 8px; background: #8b5cf6; color: white; border: none; padding: 4px 6px; border-radius: 4px; font-size: 9px; font-weight: bold; cursor: pointer; display: inline-flex; align-items: center; gap: 2px; z-index: 50;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 10px; height: 10px;"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 013.75 9.375v-4.5zM3.75 14.625c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5a1.125 1.125 0 01-1.125-1.125v-4.5zM13.5 4.875c0-.621.504-1.125 1.125-1.125h4.5c.621 0 1.125.504 1.125 1.125v4.5c0 .621-.504 1.125-1.125 1.125h-4.5A1.125 1.125 0 0113.5 9.375v-4.5z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 15h.008v.008H15V15zm0 2.25h.008v.008H15v-.008zM17.25 15h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zM15 19.5h.008v.008H15V19.5zm0-2.25h.008v.008H15v-.008zm2.25 0h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V19.5z" /></svg>
                    QR
                </button>
            </div>
            `;
        }).join('');

        updateVariantAddButtonState();
    }

    window.selectVariant = function(productId) {
        selectedVariantId = productId;
        renderVariantOptions();
    }

    function updateVariantAddButtonState() {
        const activeVar = currentVariants.find(v => v.product_id == selectedVariantId);
        const btn = document.getElementById('variantModalAddBtn');
        const warningEl = document.getElementById('variantStockWarning');
        if (!btn || !activeVar) return;

        const qtyInput = document.getElementById('variantQtyInput');
        const qty = qtyInput ? parseInt(qtyInput.value) : 1;
        const availableStock = Math.max(0, (activeVar.stock ?? 0) - getCartQty(activeVar.product_id) - getHeldQty(activeVar.product_id));

        if (availableStock <= 0) {
            btn.disabled = true;
            btn.style.opacity = '0.5';
            btn.innerText = 'Stok Habis';
            if (warningEl) {
                warningEl.innerText = 'Stok tidak mencukupi!';
                warningEl.style.display = 'block';
            }
        } else if (qty > availableStock) {
            btn.disabled = true;
            btn.style.opacity = '0.5';
            btn.innerText = 'Stok Kurang';
            if (warningEl) {
                warningEl.innerText = `Jumlah melebihi stok yang tersedia (${availableStock}).`;
                warningEl.style.display = 'block';
            }
        } else {
            btn.disabled = false;
            btn.style.opacity = '1';
            btn.innerText = 'Tambah ke Keranjang';
            if (warningEl) {
                warningEl.style.display = 'none';
            }
        }
    }

    window.updateVariantQty = function(change) {
        const qtyInput = document.getElementById('variantQtyInput');
        if (!qtyInput) return;

        let val = parseInt(qtyInput.value) + change;
        if (val < 1) val = 1;
        qtyInput.value = val;

        updateVariantAddButtonState();
    }

    window.addSelectedVariantToCart = function() {
        const activeVar = currentVariants.find(v => v.product_id == selectedVariantId);
        if (!activeVar) return;

        const qtyInput = document.getElementById('variantQtyInput');
        const qty = qtyInput ? parseInt(qtyInput.value) : 1;
        const availableStock = Math.max(0, (activeVar.stock ?? 0) - getCartQty(activeVar.product_id) - getHeldQty(activeVar.product_id));

        if (qty > availableStock) {
            window.showAlert('Stok tidak mencukupi.', 'warning');
            return;
        }

        const vName = getVariantDisplayName(activeVar);
        const displayName = vName !== 'Default' 
            ? `${activeVar.product_name} (${vName})` 
            : activeVar.product_name;

        const isDynamic = isDynamicPriceProduct(activeVar);

        if (isDynamic) {
            const promptTitle = 'Input Harga Jual';
            const promptMsg = `Masukkan harga jual untuk <strong>${displayName}</strong>:`;
            const inputId = 'promptPriceInput_' + Date.now();
            const defaultPrice = Math.round(activeVar.selling_price) || 0;
            
            const content = `
                <div style="text-align: left; margin-bottom: 12px; font-size: 14px; color: var(--dark);">${promptMsg}</div>
                <input type="number" id="${inputId}" class="form-control" placeholder="Contoh: 15000" value="${defaultPrice}" style="width: 100%; padding: 10px 12px; border: 2px solid var(--accent); border-radius: 8px; box-sizing: border-box; font-size: 15px; font-weight: 700; color: var(--dark);" min="0">
            `;
            
            openAppModal({
                size: 'modal-sm',
                title: promptTitle,
                message: content,
                okText: 'Simpan',
                cancelText: 'Batal',
                showCancel: true,
                onOk: () => {
                    const priceVal = parseFloat(document.getElementById(inputId).value) || 0;
                    if (priceVal <= 0) {
                        window.showAlert('Harga jual tidak boleh kosong atau kurang dari sama dengan 0.', 'warning');
                        return;
                    }
                    addCartWithQty(activeVar.product_id, displayName, priceVal, qty, activeVar.stock ?? 0);
                    closeVariantModal();
                }
            });

            setTimeout(() => {
                const inp = document.getElementById(inputId);
                if (inp) {
                    inp.focus();
                    inp.select();
                }
            }, 100);
            return;
        }

        addCartWithQty(activeVar.product_id, displayName, activeVar.selling_price, qty, activeVar.stock ?? 0);
        closeVariantModal();
    }

    function addCartWithQty(id, name, price, qty, maxStock) {
        let idx = cart.findIndex(i => i.id == id && i.price == parseFloat(price));
        if (idx >= 0) {
            cart[idx].qty += qty;
        } else {
            cart.push({
                id,
                name,
                price: parseFloat(price),
                qty: qty,
                maxStock: parseInt(maxStock ?? 0)
            });
        }
        updateCartBadge();
    }

    window.closeVariantModal = function() {
        const modal = document.getElementById('variantModal');
        if (modal) modal.classList.add('hidden');
    }

    let cameraStream = null;
    let capturedImageDataUrl = null;
    let activeImageMode = 'upload';

    window.switchImageMode = function(mode) {
        activeImageMode = mode;
        const uploadBtn = document.getElementById('btnModeUpload');
        const cameraBtn = document.getElementById('btnModeCamera');
        const uploadCont = document.getElementById('containerUploadImage');
        const cameraCont = document.getElementById('containerCameraImage');

        if (mode === 'upload') {
            uploadCont.style.display = 'block';
            cameraCont.style.display = 'none';

            uploadBtn.style.background = 'var(--accent)';
            uploadBtn.style.color = 'white';
            uploadBtn.style.borderColor = 'var(--accent)';

            cameraBtn.style.background = 'white';
            cameraBtn.style.color = '#4b5563';
            cameraBtn.style.borderColor = '#cbd5e1';

            stopCameraStream();
        } else {
            uploadCont.style.display = 'none';
            cameraCont.style.display = 'flex';

            cameraBtn.style.background = 'var(--accent)';
            cameraBtn.style.color = 'white';
            cameraBtn.style.borderColor = 'var(--accent)';

            uploadBtn.style.background = 'white';
            uploadBtn.style.color = '#4b5563';
            uploadBtn.style.borderColor = '#cbd5e1';
        }
    }

    window.startCamera = function() {
        navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } })
            .then(stream => {
                cameraStream = stream;
                const video = document.getElementById('cameraVideo');
                video.srcObject = stream;
                video.style.display = 'block';

                document.getElementById('capturedImage').style.display = 'none';
                document.getElementById('cameraPlaceholder').style.display = 'none';
                document.getElementById('btnStartCamera').style.display = 'none';

                const captureBtn = document.getElementById('btnCapturePhoto');
                captureBtn.disabled = false;
                captureBtn.style.opacity = '1';

                document.getElementById('btnResetPhoto').style.display = 'none';
            })
            .catch(err => {
                console.error(err);
                window.showAlert('Tidak dapat mengakses kamera: ' + err.message, 'error');
            });
    }

    window.capturePhoto = function() {
        const video = document.getElementById('cameraVideo');
        const canvas = document.getElementById('cameraCanvas');
        const context = canvas.getContext('2d');

        canvas.width = video.videoWidth || 640;
        canvas.height = video.videoHeight || 480;

        context.drawImage(video, 0, 0, canvas.width, canvas.height);
        capturedImageDataUrl = canvas.toDataURL('image/jpeg');

        const capturedImg = document.getElementById('capturedImage');
        capturedImg.src = capturedImageDataUrl;
        capturedImg.style.display = 'block';

        video.style.display = 'none';
        stopCameraStream();

        document.getElementById('btnCapturePhoto').style.display = 'none';
        document.getElementById('btnResetPhoto').style.display = 'block';
    }

    window.resetPhoto = function() {
        capturedImageDataUrl = null;
        document.getElementById('btnCapturePhoto').style.display = 'block';
        document.getElementById('btnResetPhoto').style.display = 'none';
        startCamera();
    }

    window.stopCameraStream = function() {
        if (cameraStream) {
            cameraStream.getTracks().forEach(track => track.stop());
            cameraStream = null;
        }
        const video = document.getElementById('cameraVideo');
        if (video) video.srcObject = null;
    }

    function dataURLtoFile(dataurl, filename) {
        var arr = dataurl.split(','), mime = arr[0].match(/:(.*?);/)[1],
            bstr = atob(arr[1]), n = bstr.length, u8arr = new Uint8Array(n);
        while(n--){
            u8arr[n] = bstr.charCodeAt(n);
        }
        return new File([u8arr], filename, {type:mime});
    }

    window.openAddProductModal = function() {
        // Reset form
        document.getElementById('addProductForm').reset();
        
        capturedImageDataUrl = null;
        switchImageMode('upload');
        document.getElementById('btnStartCamera').style.display = 'block';
        document.getElementById('btnCapturePhoto').style.display = 'block';
        document.getElementById('btnCapturePhoto').disabled = true;
        document.getElementById('btnCapturePhoto').style.opacity = '0.5';
        document.getElementById('btnResetPhoto').style.display = 'none';
        document.getElementById('cameraPlaceholder').style.display = 'block';
        document.getElementById('cameraVideo').style.display = 'none';
        document.getElementById('capturedImage').style.display = 'none';
        
        // Fetch categories list
        fetch('/kasir/categories')
            .then(r => r.json())
            .then(categories => {
                const select = document.getElementById('addProductCategory');
                if (select) {
                    select.innerHTML = '<option value="">Pilih Kategori (opsional)...</option>';
                    categories.forEach(cat => {
                        select.innerHTML += `<option value="${cat.category_id}">${cat.category_name}</option>`;
                    });
                }
                
                // Show modal
                const modal = document.getElementById('addProductModal');
                if (modal) modal.classList.remove('hidden');
            })
            .catch(err => {
                console.error(err);
                window.showAlert('Gagal memuat kategori.', 'error');
            });
    }

    window.closeAddProductModal = function() {
        stopCameraStream();
        const modal = document.getElementById('addProductModal');
        if (modal) modal.classList.add('hidden');
    }

    window.submitNewProduct = function(event) {
        event.preventDefault();
        
        const productName = document.getElementById('addProductName').value;
        const categoryId = document.getElementById('addProductCategory').value;
        const variant = document.getElementById('addProductVariant').value;
        const unit = document.getElementById('addProductUnit').value;
        const sellingPrice = document.getElementById('addProductSellingPrice').value;
        const initialStock = document.getElementById('addProductInitialStock').value;

        const formData = new FormData();
        formData.append('product_name', productName);
        if (categoryId) {
            formData.append('category_id', categoryId);
        }
        formData.append('variant', variant);
        formData.append('unit', unit);
        formData.append('selling_price', sellingPrice);
        formData.append('initial_stock', initialStock);

        if (activeImageMode === 'upload') {
            const imageFile = document.getElementById('addProductImage').files[0];
            if (imageFile) {
                formData.append('image', imageFile);
            }
        } else if (activeImageMode === 'camera' && capturedImageDataUrl) {
            try {
                const imageFile = dataURLtoFile(capturedImageDataUrl, 'photo.jpg');
                formData.append('image', imageFile);
            } catch (e) {
                console.error(e);
            }
        }

        fetch('/kasir/products', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: formData
        })
        .then(async (r) => {
            const data = await r.json();
            if (!r.ok) {
                throw new Error(data.message || 'Gagal menyimpan produk baru.');
            }
            return data;
        })
        .then(data => {
            window.closeAddProductModal();
            window.showAlert(data.message || 'Produk baru berhasil disimpan.', 'success');
            
            // Refresh product list
            const currentSearch = document.getElementById('productSearch')?.value || '';
            fetchProducts(currentSearch);
        })
        .catch(err => {
            console.error(err);
            window.showAlert(err.message, 'error');
        });
    }

    let html5QrcodeScanner = null;

    function openQrScanModal() {
        const modal = document.getElementById('qrScanModal');
        if (modal) modal.classList.remove('hidden');

        if (typeof Html5Qrcode !== 'undefined') {
            html5QrcodeScanner = new Html5Qrcode("reader");
            html5QrcodeScanner.start(
                { facingMode: "environment" },
                {
                    fps: 10,
                    qrbox: { width: 250, height: 250 }
                },
                (decodedText) => {
                    closeQrScanModal();
                    
                    const cleanCode = decodedText.toLowerCase().trim();
                    const matchedProd = products.find(p => 
                        p.product_name.toLowerCase().trim() === cleanCode ||
                        String(p.product_id) === cleanCode ||
                        (p.variant && p.variant.toLowerCase().trim() === cleanCode)
                    );
                    
                    if (matchedProd) {
                        addCart(matchedProd.product_id, matchedProd.product_name, matchedProd.selling_price);
                        window.showAlert(`Berhasil men-scan: ${matchedProd.product_name}`, 'success');
                    } else {
                        const searchInput = document.getElementById('productSearch');
                        if (searchInput) {
                            searchInput.value = decodedText;
                            fetchProducts(decodedText);
                        }
                        window.showAlert(`Barang dengan kode "${decodedText}" tidak ditemukan.`, 'warning');
                    }
                },
                (errorMessage) => {
                    // Ignore constant scan failures
                }
            ).catch(err => {
                console.error("Camera init error:", err);
                window.showAlert("Tidak dapat mengakses kamera: " + err, "error");
            });
        } else {
            window.showAlert("Library scanner gagal dimuat.", "error");
        }
    }

    function closeQrScanModal() {
        const modal = document.getElementById('qrScanModal');
        if (modal) modal.classList.add('hidden');

        if (html5QrcodeScanner) {
            html5QrcodeScanner.stop().then(() => {
                html5QrcodeScanner = null;
            }).catch(err => {
                console.error("Failed to stop scanner:", err);
            });
        }
    }

    let selectedQrIds = new Set();

    window.toggleQrCard = function(card, event) {
        const cb = card.querySelector('.qr-item-checkbox');
        if (!cb) return;
        
        if (event.target !== cb) {
            cb.checked = !cb.checked;
            window.toggleQrItem(cb.dataset.id, cb.checked);
        } else {
            window.toggleQrItem(cb.dataset.id, cb.checked);
        }
        
        window.updateQrCardHighlight(card, cb.checked);
    };

    window.updateQrCardHighlight = function(card, isChecked) {
        if (isChecked) {
            card.style.borderColor = '#8b5cf6';
            card.style.background = '#f5f3ff';
        } else {
            card.style.borderColor = '#e2e8f0';
            card.style.background = 'white';
        }
    };

    window.renderQrProducts = function(list) {
        const container = document.getElementById('qrProductContainer');
        if (!container) return;

        if (!list || list.length === 0) {
            container.innerHTML = '<div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #94a3b8; font-size: 13px;">Tidak ada produk ditemukan</div>';
            return;
        }

        container.innerHTML = list.map(p => {
            const variantName = getVariantDisplayName(p);
            const isChecked = selectedQrIds.has(String(p.product_id));
            const checkedAttr = isChecked ? 'checked' : '';
            const safeName = p.product_name.replace(/'/g, "\\'").replace(/"/g, '&quot;');
            const safeVariant = variantName.replace(/'/g, "\\'").replace(/"/g, '&quot;');
            const cardBorder = isChecked ? '#8b5cf6' : '#e2e8f0';
            const cardBg = isChecked ? '#f5f3ff' : 'white';

            return '<div onclick="toggleQrCard(this, event)" style="border: 1px solid ' + cardBorder + '; border-radius: 10px; padding: 12px; display: flex; align-items: center; gap: 12px; background: ' + cardBg + '; transition: all 0.2s; cursor: pointer; user-select: none;" class="qr-product-item" data-name="' + p.product_name.toLowerCase() + ' ' + variantName.toLowerCase() + '">' +
                '<input type="checkbox" class="qr-item-checkbox" data-id="' + p.product_id + '" data-name="' + safeName + '" data-variant="' + safeVariant + '" ' + checkedAttr + ' style="width: 18px; height: 18px; accent-color: #8b5cf6; cursor: pointer; flex-shrink: 0;" />' +
                '<div style="flex: 1; min-width: 0;">' +
                    '<div style="font-weight: 700; font-size: 13px; color: #1e293b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">' + p.product_name + '</div>' +
                    '<div style="font-size: 11px; color: #4f46e5; font-weight: 600; margin-top: 2px;">Varian: ' + (variantName !== 'Default' ? variantName : '-') + '</div>' +
                    '<div style="font-size: 11px; color: #64748b; margin-top: 1px;">Satuan: ' + p.unit + '</div>' +
                '</div>' +
            '</div>';
        }).join('');

        updateSelectAllQrState();
    };

    window.toggleQrItem = function(id, isChecked) {
        if (isChecked) {
            selectedQrIds.add(String(id));
        } else {
            selectedQrIds.delete(String(id));
        }
        updateSelectAllQrState();
    };

    window.toggleSelectAllQr = function(el) {
        const checkboxes = document.querySelectorAll('.qr-item-checkbox');
        checkboxes.forEach(cb => {
            cb.checked = el.checked;
            const id = cb.dataset.id;
            if (el.checked) {
                selectedQrIds.add(String(id));
            } else {
                selectedQrIds.delete(String(id));
            }
            const parent = cb.closest('.qr-product-item');
            if (parent) {
                window.updateQrCardHighlight(parent, el.checked);
            }
        });
    };

    function updateSelectAllQrState() {
        const selectAll = document.getElementById('selectAllQr');
        if (!selectAll) return;
        const checkboxes = document.querySelectorAll('.qr-item-checkbox');
        if (checkboxes.length === 0) {
            selectAll.checked = false;
            return;
        }
        const checkedCount = Array.from(checkboxes).filter(cb => cb.checked).length;
        selectAll.checked = checkedCount === checkboxes.length;
    }

    window.filterQrProducts = function() {
        const q = document.getElementById('qrSearchInput').value.toLowerCase().trim();
        const items = document.querySelectorAll('.qr-product-item');
        items.forEach(item => {
            const name = item.dataset.name || '';
            if (name.includes(q)) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    };

    window.printSelectedQrs = function() {
        const selectedCheckboxes = Array.from(document.querySelectorAll('.qr-item-checkbox')).filter(cb => cb.checked);
        if (selectedCheckboxes.length === 0) {
            window.showAlert('Silakan pilih minimal satu produk!', 'warning');
            return;
        }

        const sizeSelect = document.getElementById('labelSizeSelect');
        const sizeVal = sizeSelect ? sizeSelect.value : '50';

        let cardWidth = '50mm';
        let cardHeight = '30mm';
        let gridCols = 'repeat(auto-fill, 50mm)';
        let imgWidth = '44mm';
        let imgHeight = '15mm';
        let scaleVal = '1.5';
        let titleFontSize = '8px';
        let varFontSize = '7px';
        let barcodeHeight = '8';

        if (sizeVal === '80') {
            cardWidth = '80mm';
            cardHeight = '40mm';
            gridCols = 'repeat(auto-fill, 80mm)';
            imgWidth = '72mm';
            imgHeight = '24mm';
            scaleVal = '2';
            titleFontSize = '11px';
            varFontSize = '9px';
            barcodeHeight = '12';
        }

        const printWindow = window.open('', '_blank', 'width=800,height=600');
        
        let gridHtml = '';
        selectedCheckboxes.forEach(cb => {
            const id = cb.dataset.id;
            const name = cb.dataset.name;
            const variant = cb.dataset.variant;
            const qrUrl = 'https://bwipjs-api.metafloor.com/?bcid=code128&text=' + id + '&scale=' + scaleVal + '&rotate=N&includetext&height=' + barcodeHeight;

            gridHtml += '<div class="qr-label-card">' +
                '<img src="' + qrUrl + '" alt="Barcode" />' +
                '<div class="prod-title">' + name + '</div>' +
                '<div class="prod-var">Varian: ' + variant + '</div>' +
                '</div>';
        });

        printWindow.document.write('<html><head><title>Cetak Barcode Masal</title>' +
            '<style>' +
            'body { font-family: system-ui, -apple-system, sans-serif; padding: 10px; margin: 0; background: white; }' +
            '.grid-container { display: grid; grid-template-columns: ' + gridCols + '; gap: 5mm; }' +
            '.qr-label-card { ' +
            '  width: ' + cardWidth + '; ' +
            '  height: ' + cardHeight + '; ' +
            '  border: 1px dashed #8b5cf6; ' +
            '  border-radius: 6px; ' +
            '  padding: 1.5mm; ' +
            '  display: flex; ' +
            '  flex-direction: column; ' +
            '  align-items: center; ' +
            '  justify-content: center; ' +
            '  box-sizing: border-box; ' +
            '  background: white; ' +
            '  page-break-inside: avoid; ' +
            '}' +
            'img { width: ' + imgWidth + '; height: ' + imgHeight + '; object-fit: contain; }' +
            '.prod-title { font-weight: 700; font-size: ' + titleFontSize + '; color: #1e1b4b; margin-top: 2px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; width: 100%; text-align: center; }' +
            '.prod-var { font-size: ' + varFontSize + '; color: #6b21a8; font-weight: 600; margin-top: 1px; text-align: center; }' +
            '@media print { body { padding: 0; } .qr-label-card { border-color: #000; background: white; } }' +
            '</style></' + 'head><body>' +
            '<div class="grid-container">' + gridHtml + '</div>' +
            '<' + 'script>' +
            'window.onload = function() {' +
            '  setTimeout(function() {' +
            '    window.print();' +
            '    window.close();' +
            '  }, 1200);' +
            '};' +
            '</' + 'script></' + 'body></html>');
        printWindow.document.close();
    };

    fetchProducts('');
    setupProductSearch();
    updateCartBadge();
</script>