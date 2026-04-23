@extends('layouts.guest')

@section('title', 'Catalog Produk')

@section('content')
<section class="hero-shell mb-4">
    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
        <div>
            <p class="text-uppercase muted fw-semibold mb-1" style="font-size:.72rem; letter-spacing:.18em;">
                Catalog Produk
            </p>
            <h1 class="fw-bold mb-1" style="font-size:1.9rem;">Temukan Produk yang Anda Butuhkan</h1>
            <p class="muted mb-0">Filter cepat, tampilan bersih, dan hasil yang mudah dipahami.</p>
        </div>
        <div class="section-card py-2 px-3">
            <span class="muted">Total produk: </span>
            <strong class="text-brand">{{ $products->total() }}</strong>
        </div>
    </div>
</section>

<section class="section-card mb-4">
    <form method="GET" action="{{ route('public.catalog') }}" class="row g-2 g-lg-3 align-items-end">
        <div class="col-12 col-md-5">
            <label class="form-label mb-1 small muted">Cari Produk</label>
            <input
                type="text"
                name="search"
                value="{{ $searchQuery }}"
                class="form-control form-control-sm"
                placeholder="Contoh: pensil, buku, map">
        </div>

        <div class="col-6 col-md-3">
            <label class="form-label mb-1 small muted">Kategori</label>
            <select name="category" class="form-select form-select-sm">
                <option value="">Semua kategori</option>
                @foreach($categories as $category)
                <option value="{{ $category->category_id }}" {{ (string)$selectedCategory === (string)$category->category_id ? 'selected' : '' }}>
                    {{ $category->category_name }}
                </option>
                @endforeach
            </select>
        </div>

        <div class="col-6 col-md-2">
            <label class="form-label mb-1 small muted">Urutkan</label>
            <select name="sort_by" class="form-select form-select-sm">
                <option value="latest" {{ $sortBy === 'latest' ? 'selected' : '' }}>Terbaru</option>
                <option value="price_asc" {{ $sortBy === 'price_asc' ? 'selected' : '' }}>Harga Termurah</option>
                <option value="price_desc" {{ $sortBy === 'price_desc' ? 'selected' : '' }}>Harga Termahal</option>
            </select>
        </div>

        <div class="col-6 col-md-1 d-grid">
            <button class="btn btn-brand btn-sm">Cari</button>
        </div>

        <div class="col-6 col-md-1 d-grid">
            <a href="{{ route('public.catalog') }}" class="btn btn-outline-dark btn-sm">Reset</a>
        </div>
    </form>

    @if($searchQuery || $selectedCategory || $sortBy !== 'latest')
    <div class="d-flex flex-wrap gap-2 mt-3">
        @if($searchQuery)
        <span class="chip"><i class="bi bi-search"></i> {{ $searchQuery }}</span>
        @endif

        @if($selectedCategory)
        @php
        $activeCategory = collect($categories)->firstWhere('category_id', (int) $selectedCategory);
        @endphp
        <span class="chip"><i class="bi bi-folder2-open"></i> {{ $activeCategory->category_name ?? 'Kategori' }}</span>
        @endif

        @if($sortBy !== 'latest')
        <span class="chip"><i class="bi bi-funnel"></i>
            @if($sortBy === 'price_asc') Harga Termurah
            @elseif($sortBy === 'price_desc') Harga Termahal
            @else Terbaru
            @endif
        </span>
        @endif
    </div>
    @endif
</section>

<section class="mb-3">
    <div class="row g-3">
        @forelse($products as $product)
        <div class="col-6 col-md-4 col-lg-3">
            <x-product-card :product="$product" />
        </div>
        @empty
        <div class="col-12">
            <div class="section-card text-center py-5">
                <div class="mb-2 text-brand" style="font-size:2rem;">
                    <i class="bi bi-emoji-frown"></i>
                </div>
                <h5 class="mb-1">Produk tidak ditemukan</h5>
                <p class="muted mb-3">Coba ubah kata kunci atau reset filter katalog.</p>
                <a href="{{ route('public.catalog') }}" class="btn btn-brand btn-sm">Lihat Semua Produk</a>
            </div>
        </div>
        @endforelse
    </div>
</section>

@if($products->hasPages())
<section class="section-card">
    <div class="d-flex justify-content-center">
        {{ $products->withQueryString()->links() }}
    </div>
</section>
@endif
@endsection