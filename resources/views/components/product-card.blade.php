@props(['product'])

<article class="product-card h-100">
    <a href="{{ route('public.product.show', $product) }}" class="text-decoration-none text-dark d-block h-100">
        <div class="position-relative overflow-hidden" style="border-radius: 0.5rem 0.5rem 0 0;">
            <img
                src="{{ $product->file_url ? asset('storage/' . $product->file_url) : ($product->image ? asset('storage/products/' . $product->image) : asset('images/no-image.png')) }}"
                alt="{{ $product->product_name }}"
                class="w-100"
                style="aspect-ratio: 1 / 1; object-fit: cover;">
        </div>

        <div class="p-2 p-md-3">
            <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                <span class="badge text-bg-light border" style="font-size: 0.7rem;">{{ $product->category->category_name ?? 'Tanpa Kategori' }}</span>
                <small class="text-secondary text-end" style="font-size: 0.75rem; white-space: nowrap;">{{ $product->unit }}</small>
            </div>

            <h6 class="fw-bold mb-2" style="font-size: 0.85rem; line-height: 1.3; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                {{ $product->product_name }}
            </h6>

            <div class="d-flex justify-content-between align-items-center gap-2 mt-auto">
                <span class="fw-bold text-success" style="font-size: 0.95rem;">Rp {{ number_format($product->selling_price, 0, ',', '.') }}</span>
                <span class="btn btn-sm btn-outline-dark rounded-pill" style="font-size: 0.75rem; padding: 0.3rem 0.8rem;">Detail</span>
            </div>
        </div>
    </a>
</article>