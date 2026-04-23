@extends('layouts.guest')

@section('title', 'Dashboard Publik')

@section('content')
<section class="hero-shell mb-4 mb-lg-5">
    <div class="row g-4 align-items-center">
        <div class="col-lg-7">
            <p class="text-uppercase muted fw-semibold mb-2" style="font-size:.72rem; letter-spacing:.18em;">
                Toko alat tulis modern
            </p>

            <h1 class="fw-bold mb-3" style="font-size:2rem; line-height:1.15;">
                Belanja perlengkapan sekolah dan kantor jadi lebih nyaman.
            </h1>

            <p class="muted mb-4" style="max-width:650px;">
                Toko Intan hadir dengan produk lengkap, harga terjangkau, dan tampilan katalog yang memudahkan pelanggan memilih barang sebelum login.
            </p>

            <form action="{{ route('public.catalog') }}" method="GET" class="row g-2 mb-3" style="max-width:680px;">
                <div class="col-12 col-md-8">
                    <input
                        type="text"
                        name="search"
                        class="form-control form-control-sm"
                        placeholder="Cari produk... contoh: pensil, buku, map">
                </div>
                <div class="col-12 col-md-4 d-grid">
                    <button class="btn btn-brand btn-sm">Cari Sekarang</button>
                </div>
            </form>

            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('public.catalog') }}" class="btn btn-brand btn-sm px-3">Lihat Catalog</a>
                <a href="#featured" class="btn btn-outline-dark btn-sm px-3">Produk Unggulan</a>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="hero-art">
                <div class="bubble one"><i class="bi bi-pencil"></i></div>
                <div class="bubble two"><i class="bi bi-journal-text"></i></div>
                <div class="bubble three"><i class="bi bi-briefcase"></i></div>

                <div class="center-card">
                    <p class="mb-1 fw-semibold text-brand">Toko Intan</p>
                    <h5 class="mb-2 brand-serif">Premium Stationery</h5>
                    <small class="muted">Simple, cepat, dan user friendly untuk semua pelanggan.</small>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="mb-4 mb-lg-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="fw-bold mb-0">Kenapa Belanja di Sini?</h2>
    </div>

    <div class="row g-3">
        <div class="col-12 col-md-3">
            <div class="feature-item">
                <div class="feature-icon"><i class="bi bi-tag-fill"></i></div>
                <h6 class="fw-bold mb-1">Harga Kompetitif</h6>
                <small class="muted">Harga jelas dan ramah untuk pelajar, kantor, dan UMKM.</small>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="feature-item">
                <div class="feature-icon"><i class="bi bi-box-seam"></i></div>
                <h6 class="fw-bold mb-1">Produk Lengkap</h6>
                <small class="muted">Kategori alat tulis bervariasi dan mudah dicari.</small>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="feature-item">
                <div class="feature-icon"><i class="bi bi-lightning-charge-fill"></i></div>
                <h6 class="fw-bold mb-1">Belanja Cepat</h6>
                <small class="muted">Navigasi ringan untuk desktop maupun mobile.</small>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="feature-item">
                <div class="feature-icon"><i class="bi bi-shield-check"></i></div>
                <h6 class="fw-bold mb-1">Lebih Aman</h6>
                <small class="muted">Info produk rapi dan mudah dipahami pelanggan.</small>
            </div>
        </div>
    </div>
</section>

<section class="mb-4 mb-lg-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="fw-bold mb-0">Kategori Populer</h2>
        <a href="{{ route('public.catalog') }}" class="text-decoration-none text-brand">Lihat semua</a>
    </div>

    <div class="d-flex flex-wrap gap-2">
        @forelse($categories as $category)
        <a href="{{ route('public.catalog', ['category' => $category->category_id]) }}" class="chip">
            <i class="bi bi-folder2-open"></i>
            {{ $category->category_name }}
            <span class="muted">({{ $category->products_count ?? 0 }})</span>
        </a>
        @empty
        <p class="muted mb-0">Belum ada kategori.</p>
        @endforelse
    </div>
</section>

<section id="featured" class="mb-4 mb-lg-5">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="fw-bold mb-0">Featured Products</h2>
        <a href="{{ route('public.catalog') }}" class="text-decoration-none text-brand">Lihat semua</a>
    </div>

    <div class="row g-3">
        @forelse($featuredProducts as $product)
        <div class="col-6 col-md-4 col-lg-3">
            <x-product-card :product="$product" />
        </div>
        @empty
        <p class="muted mb-0">Belum ada produk.</p>
        @endforelse
    </div>
</section>

<section class="section-card mb-3">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h2 class="fw-bold mb-0">Pertanyaan Umum</h2>
    </div>

    <div class="accordion accordion-flush" id="faqPublic">
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                    Apakah bisa lihat produk tanpa login?
                </button>
            </h2>
            <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqPublic">
                <div class="accordion-body py-2">Bisa, dashboard dan katalog publik bisa diakses sebelum login.</div>
            </div>
        </div>

        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                    Bagaimana cara mencari produk?
                </button>
            </h2>
            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqPublic">
                <div class="accordion-body py-2">Gunakan pencarian di hero atau buka katalog lalu filter kategori.</div>
            </div>
        </div>
    </div>
</section>
@endsection