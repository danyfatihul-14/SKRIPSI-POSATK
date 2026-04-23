@props(['product'])

@php
$image = $product->file_url ? asset('storage/' . $product->file_url) : 'https://via.placeholder.com/800x600?text=Produk';
@endphp

<article class="product-card">
    <a href="{{ route('public.product.show', $product) }}" class="text-decoration-none text-dark">
        <img src="{{ $image }}" alt="{{ $product->product_name }}" class="product-thumb">

        <div class="p-3 p-lg-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="badge text-bg-light border">{{ $product->category->category_name ?? 'Tanpa Kategori' }}</span>
                <small class="text-secondary">{{ $product->unit }}</small>
            </div>

            <h6 class="fw-bold mb-3" style="min-height: 48px;">{{ $product->product_name }}</h6>

            <div class="d-flex justify-content-between align-items-center">
                <span class="fs-5 fw-bold text-success">Rp {{ number_format($product->selling_price, 0, ',', '.') }}</span>
                <span class="btn btn-sm btn-outline-dark rounded-pill px-3">Detail</span>
            </div>
        </div>
    </a>
</article>