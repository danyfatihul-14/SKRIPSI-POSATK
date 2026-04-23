@extends('layouts.guest')

@section('title', $product->product_name)

@section('content')
<section class="row g-4 mb-5 reveal">
    <div class="col-lg-6">
        <div class="soft-card overflow-hidden">
            <img
                src="{{ $product->file_url ? asset('storage/' . $product->file_url) : 'https://via.placeholder.com/1000x700?text=Produk' }}"
                alt="{{ $product->product_name }}"
                class="w-100"
                style="aspect-ratio: 4/3; object-fit: cover;">
        </div>
    </div>

    <div class="col-lg-6">
        <p class="text-uppercase small fw-semibold text-success mb-2">{{ $product->category->category_name ?? 'Tanpa Kategori' }}</p>
        <h1 class="display-6 fw-bold mb-3">{{ $product->product_name }}</h1>
        <h2 class="fw-bold text-success mb-2">Rp {{ number_format($product->selling_price, 0, ',', '.') }}</h2>
        <p class="text-secondary mb-4">Satuan: {{ $product->unit }}</p>

        <a href="{{ route('public.catalog') }}" class="btn btn-outline-dark rounded-pill px-4">Kembali ke Catalog</a>
    </div>
</section>

<section class="reveal">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="section-title h4 mb-0">Produk Serupa</h3>
    </div>

    <div class="row g-4">
        @forelse($relatedProducts as $item)
        <div class="col-12 col-sm-6 col-lg-3">
            <x-product-card :product="$item" />
        </div>
        @empty
        <p class="text-secondary">Belum ada produk serupa.</p>
        @endforelse
    </div>
</section>
@endsection