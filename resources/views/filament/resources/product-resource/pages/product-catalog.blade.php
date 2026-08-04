<x-filament-panels::page>
    <style>
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 12px;
        }

        .product-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05), 0 1px 2px rgba(0, 0, 0, 0.1);
            padding: 12px;
            position: relative;
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            border: 1px solid #f1f5f9;
        }

        .product-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .edit-icon {
            position: absolute;
            top: 6px;
            right: 6px;
            background: rgba(59, 130, 246, 0.9);
            border-radius: 50%;
            padding: 5px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
            z-index: 10;
            line-height: 0;
            transition: background 0.2s;
        }

        .edit-icon:hover {
            background: #2563eb;
        }

        .edit-icon svg {
            width: 12px;
            height: 12px;
            color: white;
            display: block;
        }

        .product-title {
            font-size: 12px;
            font-weight: 700;
            line-height: 1.4;
            margin-top: 4px;
            margin-bottom: 4px;
            color: #1e293b;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 34px;
        }

        .product-category {
            font-size: 10px;
            color: #64748b;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .product-info {
            font-size: 10px;
            margin-bottom: 8px;
            border-top: 1px dashed #f1f5f9;
            padding-top: 6px;
        }

        .product-info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 3px;
        }

        .product-label {
            color: #64748b;
        }

        .product-value {
            font-weight: 700;
            font-size: 11px;
            color: #0f172a;
        }

        .product-stock-ok {
            color: #10b981;
            font-weight: 700;
        }

        .product-stock-out {
            color: #ef4444;
            font-weight: 700;
        }

        .product-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px;
            margin-top: auto;
            padding-top: 8px;
            border-top: 1px solid #f1f5f9;
        }

        .btn-edit,
        .btn-delete,
        .btn-predict {
            padding: 7px 0;
            border: none;
            border-radius: 6px;
            color: white;
            font-size: 10px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s;
            text-align: center;
            text-decoration: none;
            display: block;
        }

        .btn-predict {
            grid-column: span 2;
            background: #10b981;
        }

        .btn-predict:hover {
            background: #059669;
        }

        .btn-edit {
            background: #3b82f6;
        }

        .btn-edit:hover {
            background: #2563eb;
        }

        .btn-delete {
            background: #ef4444;
        }

        .btn-delete:hover {
            background: #dc2626;
        }

        .warning-badge {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fca5a5;
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 9px;
            font-weight: 700;
            display: inline-block;
        }

        .incomplete-callout {
            background: #fffbebf5;
            border: 1px solid #fcd34d;
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 12px;
            color: #92400e;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 10px;
        }
    </style>

    <div class="space-y-3">
        {{-- Notification Banner for Incomplete Data --}}
        @if($countIncomplete > 0)
        <div class="incomplete-callout">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-size: 16px;">⚠️</span>
                <div>
                    <strong>Pemberitahuan Data Produk Inkomplit:</strong>
                    Terdapat <strong>{{ $countIncomplete }}</strong> produk belum lengkap 
                    (<span>📷 {{ $countNoImage }} tanpa gambar</span>, 
                    <span>🏷️ {{ $countNoCategory }} tanpa kategori</span>, 
                    <span>💰 {{ $countNoPurchasePrice }} tanpa harga beli</span>).
                </div>
            </div>
            @if(empty($missingFilter))
            <button wire:click="$set('missingFilter', 'incomplete')" style="background: #d97706; color: white; border: none; border-radius: 6px; padding: 4px 10px; font-size: 11px; font-weight: 700; cursor: pointer; white-space: nowrap;">
                Tampilkan Produk Inkomplit
            </button>
            @else
            <button wire:click="$set('missingFilter', '')" style="background: #4b5563; color: white; border: none; border-radius: 6px; padding: 4px 10px; font-size: 11px; font-weight: 700; cursor: pointer; white-space: nowrap;">
                Lihat Semua Produk
            </button>
            @endif
        </div>
        @endif

        {{-- Filters & Search --}}
        <div class="flex gap-2 items-center flex-wrap">
            <x-filament::input.wrapper class="w-44">
                <x-filament::input.select wire:model.live="storeId" class="text-xs">
                    @foreach($stores as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>

            <x-filament::input.wrapper class="w-64">
                <x-filament::input.select wire:model.live="missingFilter" class="text-xs">
                    <option value="">Semua Data Produk</option>
                    <option value="incomplete">⚠️ Semua Data Inkomplit ({{ $countIncomplete }})</option>
                    <option value="no_image">📷 Tanpa Gambar ({{ $countNoImage }})</option>
                    <option value="no_category">🏷️ Tanpa Kategori ({{ $countNoCategory }})</option>
                    <option value="no_purchase_price">💰 Tanpa Harga Beli ({{ $countNoPurchasePrice }})</option>
                </x-filament::input.select>
            </x-filament::input.wrapper>

            <x-filament::input.wrapper class="flex-1 min-w-[180px]">
                <x-filament::input
                    type="text"
                    wire:model.live="search"
                    placeholder="Cari produk..."
                    class="text-xs" />
            </x-filament::input.wrapper>

            <x-filament::button
                wire:click="loadProducts"
                color="primary"
                size="sm">
                Refresh
            </x-filament::button>
        </div>

        {{-- Products Grid --}}
        @if(count($products) > 0)
        <div class="product-grid">
            @foreach($products as $product)
            <div class="product-card">
                {{-- Edit Icon--}}
                <a href="{{ route('filament.admin.resources.products.edit', ['record' => $product['product_id'], 'store_id' => $storeId]) }}" class="edit-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </a>

                {{-- Image Container (Golden Aspect Ratio Hack) --}}
                <div style="position: relative; width: 100%; padding-top: 100%; background: #f1f5f9; border-radius: 8px; overflow: hidden; margin-bottom: 8px; border: 1px solid #f1f5f9;">
                    @if($product['file_url'])
                    <img
                        src="{{ asset('storage/' . $product['file_url']) }}"
                        alt="{{ $product['product_name'] }}"
                        style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover;"
                        onerror="this.src='https://placehold.co/200x200?text=No+Image'">
                    @else
                    <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: #f8fafc;">
                        <span style="color: #94a3b8; font-size: 10px; font-weight: 600;">No Image</span>
                    </div>
                    @endif
                </div>

                {{-- Incomplete Warning Badges --}}
                @if(!empty($product['missing']))
                <div style="display: flex; flex-wrap: wrap; gap: 3px; margin-bottom: 6px;">
                    @foreach($product['missing'] as $tag)
                        <span class="warning-badge">⚠️ {{ $tag }}</span>
                    @endforeach
                </div>
                @endif

                {{-- Info --}}
                <h3 class="product-title">{{ $product['product_name'] }}</h3>

                <p class="product-category" style="color:#2563eb; font-weight:700; margin-bottom:2px; font-size:10px;">
                    Varian: {{ $product['variant'] ?? '-' }}
                </p>

                <p class="product-category" style="color:#4b5563; font-weight:700; margin-bottom:2px; font-size:10px;">
                    Satuan: {{ $product['unit'] ?? '-' }}
                </p>

                <p class="product-category" style="font-size:10px; color: {{ empty($product['category_id']) ? '#dc2626; font-weight:700;' : '#64748b;' }}">
                    Kategori: {{ $product['category_name'] ?? 'Belum ada' }}
                </p>

                <div class="product-info">
                    <div class="product-info-row">
                        <span class="product-label">Hrg Beli:</span>
                        @if(!empty($product['is_dynamic']))
                            <span class="product-value" style="color: #2563eb; font-weight: 700;">Dinamis</span>
                        @else
                            <span class="product-value" style="{{ $product['purchase_price'] <= 0 ? 'color: #dc2626; font-weight: 700;' : '' }}">
                                {{ $product['purchase_price'] > 0 ? 'Rp ' . number_format($product['purchase_price'], 0, ',', '.') : 'Belum diisi' }}
                            </span>
                        @endif
                    </div>

                    <div class="product-info-row">
                        <span class="product-label">Hrg Jual:</span>
                        <span class="product-value">Rp {{ number_format($product['selling_price'], 0, ',', '.') }}</span>
                    </div>

                    <div class="product-info-row">
                        <span class="product-label">Stok:</span>
                        <span class="{{ $product['stock'] > 0 ? 'product-stock-ok' : 'product-stock-out' }}">
                            {{ $product['stock'] }}
                        </span>
                    </div>
                </div>

                <div class="product-actions">
                    <a href="{{ route('filament.admin.resources.products.edit', ['record' => $product['product_id'], 'store_id' => $storeId]) }}" class="btn-edit">
                        Edit
                    </a>
                    <button
                        wire:click="deleteProduct({{ $product['product_id'] }})"
                        wire:confirm="Yakin hapus produk ini?"
                        class="btn-delete">
                        Hapus
                    </button>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div style="text-align: center; padding: 32px 0;">
            <p style="color: #6b7280; font-size: 12px;">Tidak ada produk ditemukan.</p>
        </div>
        @endif
    </div>

</x-filament-panels::page>