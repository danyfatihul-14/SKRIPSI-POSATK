<x-filament-panels::page>
    <div class="space-y-6">
        <div style="background: white; border-radius: 12px; border: 1px solid #e2e8f0; padding: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; flex-direction: column;">
            
            <!-- Header Controls -->
            <div style="display: flex; gap: 16px; align-items: center; margin-bottom: 20px; flex-wrap: wrap; border-bottom: 1px solid #f1f5f9; padding-bottom: 16px; justify-content: space-between;">
                <div style="flex: 1; min-width: 250px;">
                    <input type="text" id="barcodeSearchInput" oninput="filterBarcodeProducts()" placeholder="Cari nama produk atau varian..." style="width: 100%; border: 1px solid #cbd5e1; border-radius: 10px; padding: 8px 16px; font-size: 13px; outline: none; box-shadow: none;" />
                </div>

                <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 8px; font-size: 13px; color: #475569;">
                        <span style="font-weight: 700;">Ukuran Barcode:</span>
                        <select id="labelSizeSelectAdmin" style="border: 1px solid #cbd5e1; border-radius: 10px; padding: 8px 12px; font-size: 13px; outline: none; cursor: pointer; background: white;">
                            <option value="50">50mm x 30mm (Kecil - Pensil/Pulpen)</option>
                            <option value="80">80mm x 40mm (Sedang - Buku/Lainnya)</option>
                        </select>
                    </div>
                    
                    <label style="display: inline-flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 700; color: #475569; cursor: pointer; user-select: none;">
                        <input type="checkbox" id="selectAllBarcodeAdmin" onchange="toggleSelectAllBarcodeAdmin(this)" style="width: 18px; height: 18px; border-radius: 4px; border: 1px solid #cbd5e1; cursor: pointer; accent-color: #8b5cf6;" />
                        Pilih Semua
                    </label>

                    <button type="button" onclick="printSelectedBarcodesAdmin()" style="background: #8b5cf6; border: none; padding: 8px 20px; border-radius: 10px; font-size: 13px; font-weight: 700; color: white; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; box-shadow: 0 4px 12px rgba(139, 92, 246, 0.25); transition: background 0.2s;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 16px; height: 16px;"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.89l-1.92-1.92L2.88 13.89M17.28 13.89l1.92-1.92 1.92 1.92M9 19.5h6a2.25 2.25 0 002.25-2.25V15M9 19.5a2.25 2.25 0 01-2.25-2.25V15m12 4.5a2.25 2.25 0 002.25-2.25V15m-15 0h15M9 4.5h6a2.25 2.25 0 012.25 2.25V9M9 4.5A2.25 2.25 0 006.75 6.75V9m10.5-2.25A2.25 2.25 0 0119.5 9m0 0v6M4.5 9v6M9 9h6" /></svg>
                        Cetak Barcode Terpilih
                    </button>
                </div>
            </div>

            <!-- Products List Grid -->
            <div id="barcodeProductContainerAdmin" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 14px; max-height: 60vh; overflow-y: auto; padding: 4px;">
                @foreach($products as $p)
                    @php
                        $variantName = $p['variant'] ?: '-';
                    @endphp
                    <div onclick="toggleBarcodeItemAdmin(this, event)" style="border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; display: flex; align-items: center; gap: 12px; background: white; transition: all 0.2s; cursor: pointer; user-select: none;" class="barcode-product-item-admin" data-name="{{ strtolower($p['product_name']) }} {{ strtolower($variantName) }}">
                        <input type="checkbox" class="barcode-item-checkbox-admin" data-id="{{ $p['product_id'] }}" data-name="{{ $p['product_name'] }}" data-variant="{{ $variantName }}" onchange="updateSelectAllBarcodeAdminState()" style="width: 18px; height: 18px; accent-color: #8b5cf6; cursor: pointer; flex-shrink: 0;" />
                        <div style="flex: 1; min-width: 0;">
                            <div style="font-weight: 700; font-size: 13px; color: #1e293b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $p['product_name'] }}</div>
                            <div style="font-size: 11px; color: #4f46e5; font-weight: 600; margin-top: 2px;">Varian: {{ $variantName }}</div>
                            <div style="font-size: 11px; color: #64748b; margin-top: 1px;">Satuan: {{ $p['unit'] ?: '-' }}</div>
                        </div>
                    </div>
                @endforeach
            </div>

        </div>

        <script>
            window.filterBarcodeProducts = function() {
                const q = document.getElementById('barcodeSearchInput').value.toLowerCase().trim();
                const items = document.querySelectorAll('.barcode-product-item-admin');
                items.forEach(item => {
                    const name = item.dataset.name || '';
                    if (name.includes(q)) {
                        item.style.display = 'flex';
                    } else {
                        item.style.display = 'none';
                    }
                });
            }

            window.toggleBarcodeItemAdmin = function(card, event) {
                const cb = card.querySelector('.barcode-item-checkbox-admin');
                if (!cb) return;

                // If the user clicked directly on the checkbox, let the browser handle it.
                // Otherwise, we toggle it manually.
                if (event.target !== cb) {
                    cb.checked = !cb.checked;
                }

                window.updateCardHighlightAdmin(card, cb.checked);
                window.updateSelectAllBarcodeAdminState();
            }

            window.updateCardHighlightAdmin = function(card, isChecked) {
                if (isChecked) {
                    card.style.borderColor = '#8b5cf6';
                    card.style.background = '#f5f3ff';
                } else {
                    card.style.borderColor = '#e2e8f0';
                    card.style.background = 'white';
                }
            }

            window.updateAllCardHighlightsAdmin = function() {
                const checkboxes = document.querySelectorAll('.barcode-item-checkbox-admin');
                checkboxes.forEach(cb => {
                    const card = cb.closest('.barcode-product-item-admin');
                    if (card) {
                        window.updateCardHighlightAdmin(card, cb.checked);
                    }
                });
            }

            window.toggleSelectAllBarcodeAdmin = function(el) {
                const checkboxes = document.querySelectorAll('.barcode-item-checkbox-admin');
                checkboxes.forEach(cb => {
                    const parent = cb.closest('.barcode-product-item-admin');
                    if (parent && parent.style.display !== 'none') {
                        cb.checked = el.checked;
                        window.updateCardHighlightAdmin(parent, cb.checked);
                    }
                });
            }

            window.updateSelectAllBarcodeAdminState = function() {
                const selectAll = document.getElementById('selectAllBarcodeAdmin');
                if (!selectAll) return;
                const checkboxes = Array.from(document.querySelectorAll('.barcode-item-checkbox-admin'))
                    .filter(cb => {
                        const parent = cb.closest('.barcode-product-item-admin');
                        return parent && parent.style.display !== 'none';
                    });
                if (checkboxes.length === 0) {
                    selectAll.checked = false;
                    return;
                }
                const checkedCount = checkboxes.filter(cb => cb.checked).length;
                selectAll.checked = checkedCount === checkboxes.length;

                window.updateAllCardHighlightsAdmin();
            }

            window.printSelectedBarcodesAdmin = function() {
                const selectedCheckboxes = Array.from(document.querySelectorAll('.barcode-item-checkbox-admin')).filter(cb => cb.checked);
                if (selectedCheckboxes.length === 0) {
                    alert('Silakan pilih minimal satu produk!');
                    return;
                }

                const sizeSelect = document.getElementById('labelSizeSelectAdmin');
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

                    gridHtml += '\x3cdiv class="qr-label-card"\x3e' +
                        '\x3cimg src="' + qrUrl + '" alt="Barcode" /\x3e' +
                        '\x3cdiv class="prod-title"\x3e' + name + '\x3c/div\x3e' +
                        '\x3cdiv class="prod-var"\x3eVarian: ' + variant + '\x3c/div\x3e' +
                        '\x3c/div\x3e';
                });

                printWindow.document.write('\x3chtml\x3e\x3chead\x3e\x3ctitle\x3eCetak Barcode Masal\x3c/title\x3e' +
                    '\x3cstyle\x3e' +
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
                    '\x3c/style\x3e\x3c/head\x3e\x3cbody\x3e' +
                    '\x3cdiv class="grid-container"\x3e' + gridHtml + '\x3c/div\x3e' +
                    '\x3cscript\x3e' +
                    'window.onload = function() {' +
                    '  setTimeout(function() {' +
                    '    window.print();' +
                    '    window.close();' +
                    '  }, 1200);' +
                    '};' +
                    '\x3c/script\x3e\x3c/body\x3e\x3c/html\x3e');
                printWindow.document.close();
            }
        </script>
    </div>
</x-filament-panels::page>
