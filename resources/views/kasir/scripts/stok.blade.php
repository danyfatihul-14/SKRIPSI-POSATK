{{-- filepath: e:\Polinema\Semester8\Skripsi\pos\resources\views\kasir\scripts\stok.blade.php --}}
<script>
    var stockProducts = window.stockProducts || [];
    var activeAdjustProductId = window.activeAdjustProductId || null;

    function initStockPage() {
        // Fetch all products in the store to get up-to-date stock information
        fetch('/kasir/search?q=')
            .then(r => r.json())
            .then(data => {
                stockProducts = data;
                renderStockTable(data);
                populateConvertDropdowns(data);
            })
            .catch(e => console.error("Gagal memuat data stok:", e));
    }

    function switchStokTab(tab) {
        document.querySelectorAll('#page-stok .stok-tab-btn').forEach(btn => btn.classList.remove('active'));
        document.querySelectorAll('#page-stok .stok-panel').forEach(panel => panel.classList.remove('active'));

        const activeBtn = Array.from(document.querySelectorAll('#page-stok .stok-tab-btn')).find(btn => btn.innerText.toLowerCase().includes(tab === 'adjust' ? 'penyesuaian' : 'konversi'));
        if (activeBtn) activeBtn.classList.add('active');

        const activePanel = document.getElementById('panel-stok-' + tab);
        if (activePanel) activePanel.classList.add('active');

        if (tab === 'convert') {
            updateConvertPreview();
        }
    }

    function renderStockTable(list) {
        const tbody = document.getElementById('stockTableBody');
        if (!tbody) return;

        if (!list.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" style="text-align: center; color: #64748b; padding: 20px;">
                        Tidak ada produk ditemukan.
                    </td>
                </tr>
            `;
            return;
        }

        tbody.innerHTML = list.map(prod => {
            const variantText = prod.variant && prod.variant !== '-' ? prod.variant : '-';
            const escapedName = prod.product_name.replace(/'/g, "\\'");
            return `
                <tr>
                    <td style="font-weight: 600; color: var(--dark);">${prod.product_name}</td>
                    <td style="color: #4b5563;">${variantText}</td>
                    <td><span class="badge-unit">${prod.unit}</span></td>
                    <td style="text-align: center; font-weight: 700; color: ${prod.stock <= 0 ? '#ef4444' : 'var(--dark)'};">${prod.stock ?? 0}</td>
                    <td style="text-align: center;">
                        <button type="button" class="btn-primary" style="padding: 8px 14px; font-size: 14px; border: none; border-radius: 6px;"
                            onclick="openStockAdjustModal('${prod.product_id}', '${escapedName}', ${prod.stock ?? 0})">
                            Barang Masuk
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    function setupStockSearch() {
        const searchInput = document.getElementById('stockSearchInput');
        if (!searchInput) return;

        searchInput.addEventListener('input', function() {
            const keyword = this.value.toLowerCase().trim();
            const filtered = stockProducts.filter(p => 
                p.product_name.toLowerCase().includes(keyword) || 
                (p.variant && p.variant.toLowerCase().includes(keyword))
            );
            renderStockTable(filtered);
        });
    }

    function openStockAdjustModal(productId, productName, currentQty) {
        activeAdjustProductId = productId;
        
        const titleEl = document.getElementById('stockAdjustModalTitle');
        const nameEl = document.getElementById('adjustModalProductName');
        const currentQtyEl = document.getElementById('adjustModalCurrentQty');
        const newQtyInput = document.getElementById('adjustModalNewQty');

        if (nameEl) nameEl.innerText = productName;
        if (currentQtyEl) currentQtyEl.innerText = currentQty;
        if (newQtyInput) {
            newQtyInput.value = 0;
            newQtyInput.focus();
        }

        const modal = document.getElementById('stockAdjustModal');
        if (modal) modal.classList.remove('hidden');
    }

    function closeStockAdjustModal() {
        const modal = document.getElementById('stockAdjustModal');
        if (modal) modal.classList.add('hidden');
        activeAdjustProductId = null;
    }

    var isSubmittingStockAdjust = false;

    function saveStockAdjustment() {
        if (!activeAdjustProductId || isSubmittingStockAdjust) return;

        const newQtyInput = document.getElementById('adjustModalNewQty');
        const incomingQty = parseInt(newQtyInput ? newQtyInput.value : 0, 10);

        if (isNaN(incomingQty) || incomingQty <= 0) {
            window.showAlert('Jumlah barang masuk harus lebih dari 0.', 'warning');
            return;
        }

        const currentQtyEl = document.getElementById('adjustModalCurrentQty');
        const currentQty = parseInt(currentQtyEl ? currentQtyEl.innerText : 0, 10) || 0;
        const totalQuantity = currentQty + incomingQty;

        isSubmittingStockAdjust = true;
        const btnSave = document.querySelector('#stockAdjustModal button[onclick="saveStockAdjustment()"]');
        if (btnSave) {
            btnSave.disabled = true;
            btnSave.innerText = '⏳ Menyimpan...';
        }

        fetch('/kasir/stok/adjust', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                product_id: activeAdjustProductId,
                quantity: totalQuantity
            })
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                window.showAlert(res.message || 'Stok berhasil diperbarui.', 'success');
                closeStockAdjustModal();
                initStockPage();
                if (typeof fetchProducts === 'function') {
                    fetchProducts('', true);
                }
            } else {
                window.showAlert(res.message || 'Gagal memperbarui stok.', 'error');
            }
        })
        .catch(err => {
            console.error('Error stock adjust:', err);
            window.showAlert('Terjadi kesalahan jaringan.', 'error');
        })
        .finally(() => {
            isSubmittingStockAdjust = false;
            if (btnSave) {
                btnSave.disabled = false;
                btnSave.innerText = 'Simpan Barang Masuk';
            }
        });
    }

    function populateConvertDropdowns(list) {
        const sourceSelect = document.getElementById('convertSourceProduct');
        const targetSelect = document.getElementById('convertTargetProduct');
        if (!sourceSelect || !targetSelect) return;

        const savedSourceVal = sourceSelect.value;
        const savedTargetVal = targetSelect.value;

        // Populate Source Select
        sourceSelect.innerHTML = '<option value="">-- Pilih Produk Asal --</option>' + 
            list.map(p => {
                const variantText = p.variant && p.variant !== '-' ? ` (${p.variant})` : '';
                return `<option value="${p.product_id}" data-unit="${p.unit}" data-stock="${p.stock}" data-name="${p.product_name}">
                    ${p.product_name}${variantText} [${p.unit}] (Stok: ${p.stock})
                </option>`;
            }).join('');

        // Populate Target Select
        targetSelect.innerHTML = '<option value="">-- Pilih Produk Tujuan --</option>' + 
            list.map(p => {
                const variantText = p.variant && p.variant !== '-' ? ` (${p.variant})` : '';
                return `<option value="${p.product_id}" data-unit="${p.unit}" data-stock="${p.stock}" data-name="${p.product_name}">
                    ${p.product_name}${variantText} [${p.unit}] (Stok: ${p.stock})
                </option>`;
            }).join('');

        // Re-set values if they still exist
        sourceSelect.value = savedSourceVal;
        targetSelect.value = savedTargetVal;
    }

    function updateConvertPreview() {
        const sourceSelect = document.getElementById('convertSourceProduct');
        const targetSelect = document.getElementById('convertTargetProduct');
        const qtyInput = document.getElementById('convertQty');
        const ratioInput = document.getElementById('convertRatio');
        const previewBox = document.getElementById('convertPreviewBox');
        const previewText = document.getElementById('convertPreviewText');

        if (!sourceSelect || !targetSelect || !qtyInput || !ratioInput || !previewBox || !previewText) return;

        const sourceOpt = sourceSelect.options[sourceSelect.selectedIndex];
        const targetOpt = targetSelect.options[targetSelect.selectedIndex];

        if (!sourceSelect.value || !targetSelect.value) {
            previewBox.style.display = 'none';
            return;
        }

        const qty = parseInt(qtyInput.value) || 1;
        const ratio = parseInt(ratioInput.value) || 12;

        const sourceName = sourceOpt.getAttribute('data-name');
        const sourceUnit = sourceOpt.getAttribute('data-unit');
        const sourceStock = parseInt(sourceOpt.getAttribute('data-stock')) || 0;

        const targetName = targetOpt.getAttribute('data-name');
        const targetUnit = targetOpt.getAttribute('data-unit');

        const finalTargetQty = qty * ratio;

        previewText.innerHTML = `<strong>Rencana Konversi:</strong><br/>
            - Mengurangi stok <strong>${sourceName}</strong> sebanyak <strong>${qty} ${sourceUnit}</strong> (Sisa stok: ${sourceStock - qty}).<br/>
            - Menambah stok <strong>${targetName}</strong> sebanyak <strong>${finalTargetQty} ${targetUnit}</strong>.`;
        
        previewBox.style.display = 'flex';
    }

    var isSubmittingStockConvert = false;

    function handleStockConvert(e) {
        e.preventDefault();

        if (isSubmittingStockConvert) return;

        const sourceSelect = document.getElementById('convertSourceProduct');
        const targetSelect = document.getElementById('convertTargetProduct');
        const qtyInput = document.getElementById('convertQty');
        const ratioInput = document.getElementById('convertRatio');

        if (!sourceSelect.value || !targetSelect.value) {
            window.showAlert('Harap pilih produk asal dan produk tujuan!', 'warning');
            return;
        }

        if (sourceSelect.value === targetSelect.value) {
            window.showAlert('Produk asal dan produk tujuan tidak boleh sama!', 'warning');
            return;
        }

        const qty = parseInt(qtyInput.value);
        const ratio = parseInt(ratioInput.value);
        const sourceOpt = sourceSelect.options[sourceSelect.selectedIndex];
        const sourceStock = parseInt(sourceOpt.getAttribute('data-stock')) || 0;

        if (isNaN(qty) || qty <= 0) {
            window.showAlert('Jumlah produk asal harus lebih dari 0!', 'warning');
            return;
        }

        if (isNaN(ratio) || ratio <= 0) {
            window.showAlert('Rasio konversi harus lebih dari 0!', 'warning');
            return;
        }

        if (qty > sourceStock) {
            window.showAlert('Stok produk asal tidak mencukupi untuk dikonversi!', 'warning');
            return;
        }

        window.showConfirm('Apakah Anda yakin ingin memecah/mengkonversi stok produk ini?', () => {
            isSubmittingStockConvert = true;
            const params = new URLSearchParams();
            params.append('source_product_id', sourceSelect.value);
            params.append('target_product_id', targetSelect.value);
            params.append('source_quantity', qty);
            params.append('ratio', ratio);

            fetch('{{ route("kasir.stok.convert") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: params
            })
            .then(r => r.json())
            .then(res => {
                if (res.status === 'success') {
                    window.showAlert(res.message, 'success');
                    // Reset form qty
                    qtyInput.value = 1;
                    // Refresh data
                    initStockPage();
                    // Also trigger reload of cashier products if the function exists
                    if (typeof fetchProducts === 'function') {
                        fetchProducts('', true);
                    }
                } else {
                    window.showAlert(res.message || 'Gagal melakukan konversi.', 'error');
                }
            })
            .catch(e => {
                console.error(e);
                window.showAlert('Terjadi kesalahan jaringan saat memproses konversi stok.', 'error');
            })
            .finally(() => {
                isSubmittingStockConvert = false;
            });
        });
    }

    // Initialize searches and page actions
    document.addEventListener('DOMContentLoaded', () => {
        setupStockSearch();
    });
</script>
