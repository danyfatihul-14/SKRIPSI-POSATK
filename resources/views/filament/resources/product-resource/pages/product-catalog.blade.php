<x-filament-panels::page>
    <style>
        .product-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 8px;
        }

        @media (min-width: 640px) {
            .product-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        @media (min-width: 768px) {
            .product-grid {
                grid-template-columns: repeat(5, 1fr);
            }
        }

        @media (min-width: 1024px) {
            .product-grid {
                grid-template-columns: repeat(6, 1fr);
            }
        }

        @media (min-width: 1280px) {
            .product-grid {
                grid-template-columns: repeat(7, 1fr);
            }
        }

        .product-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 10px;
            position: relative;
            transition: box-shadow 0.2s;
        }

        .product-card:hover {
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .product-image {
            width: 100%;
            aspect-ratio: 1;
            object-fit: cover;
            border-radius: 6px;
            margin-bottom: 8px;
            background: #f3f4f6;
        }

        .edit-icon {
            position: absolute;
            top: 4px;
            right: 4px;
            background: #3b82f6;
            border-radius: 50%;
            padding: 4px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            z-index: 10;
            line-height: 0;
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
            font-size: 11px;
            font-weight: 600;
            line-height: 1.3;
            margin-bottom: 4px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 28px;
        }

        .product-category {
            font-size: 9px;
            color: #6b7280;
            margin-bottom: 6px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .product-info {
            font-size: 9px;
            margin-bottom: 6px;
        }

        .product-info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2px;
        }

        .product-label {
            color: #6b7280;
        }

        .product-value {
            font-weight: 600;
            font-size: 10px;
        }

        .product-stock-ok {
            color: #059669;
            font-weight: 700;
        }

        .product-stock-out {
            color: #dc2626;
            font-weight: 700;
        }

        .product-actions {
            display: flex;
            gap: 4px;
        }

        .btn-edit,
        .btn-delete {
            flex: 1;
            padding: 6px 0;
            border: none;
            border-radius: 5px;
            color: white;
            font-size: 9px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.2s;
            text-align: center;
            text-decoration: none;
            display: block;
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
    </style>

    <div class="space-y-2">
        {{-- Search --}}
        <div class="flex gap-2 items-center">
            <x-filament::input.wrapper class="flex-1">
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
                <a href="{{ route('filament.admin.resources.products.edit', $product['product_id']) }}" class="edit-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </a>

                {{-- Image --}}
                @if($product['file_url'])
                <img
                    src="{{ asset('storage/' . $product['file_url']) }}"
                    alt="{{ $product['product_name'] }}"
                    class="product-image"
                    onerror="this.src='https://via.placeholder.com/200x200?text=No+Image'">
                @else
                <div class="product-image" style="display: flex; align-items: center; justify-content: center;">
                    <span style="color: #9ca3af; font-size: 9px;">No Image</span>
                </div>
                @endif

                {{-- Info --}}
                <h3 class="product-title">{{ $product['product_name'] }}</h3>
                <p class="product-category">{{ $product['category_name'] }}</p>

                <div class="product-info">
                    <div class="product-info-row">
                        <span class="product-label">Harga:</span>
                        <span class="product-value">Rp {{ number_format($product['selling_price'], 0, ',', '.') }}</span>
                    </div>

                    <div class="product-info-row">
                        <span class="product-label">Stok:</span>
                        <span class="{{ $product['stock'] > 0 ? 'product-stock-ok' : 'product-stock-out' }}">
                            {{ $product['stock'] }}
                        </span>
                    </div>

                    @if($product['discount'] > 0)
                    <div class="product-info-row">
                        <span class="product-label">Diskon:</span>
                        <span class="product-value" style="color: #f97316;">Rp {{ number_format($product['discount'], 0, ',', '.') }}</span>
                    </div>
                    @endif
                </div>

                {{-- Actions --}}
                <div class="product-actions">
                    <a href="{{ route('filament.admin.resources.products.edit', $product['product_id']) }}" class="btn-edit">
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