@extends('layouts.guest')

@section('title', 'Dashboard Publik')

@section('content')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    .public-dashboard {
        font-family: 'Inter', sans-serif !important;
    }

    /* Responsive font */
    h1 {
        font-size: clamp(1.4rem, 2.5vw, 1.8rem);
    }

    h2 {
        font-size: clamp(1.2rem, 2vw, 1.4rem);
    }

    p,
    small {
        font-size: clamp(0.85rem, 1.8vw, 0.95rem);
    }

    @media (max-width: 768px) {
        .hero-shell {
            text-align: center;
        }

        .hero-shell h1 {
            max-width: 100% !important;
        }

        .hero-shell p {
            max-width: 100% !important;
            margin: 0 auto;
        }

        .hero-shell form {
            max-width: 100% !important;
        }
    }

    .btn {
        width: auto;
    }

    @media (max-width: 576px) {
        .btn {
            width: 100%;
        }
    }

    .chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        white-space: nowrap;
    }

    @media (max-width: 576px) {
        .chip {
            font-size: 0.8rem;
            padding: 0.4rem 0.7rem;
        }
    }

    .feature-item {
        padding: 1rem;
        border-radius: 0.75rem;
        background: #fff;
    }

    @media (max-width: 768px) {
        .feature-item {
            text-align: center;
        }
    }

    .section-card {
        padding: 1.5rem;
        border-radius: 1rem;
        background: #fff;
    }

    @media (max-width: 576px) {
        .section-card {
            padding: 1rem;
        }
    }
</style>

<div class="public-dashboard">
    <div class="container-fluid px-3 px-md-4">
        <section class="hero-shell mb-5">
            <div class="row g-4 align-items-center">
                <div class="col-lg-7">
                    <p class="text-uppercase fw-medium mb-2" style="font-size:0.7rem; letter-spacing:0.12em; color: #6c757d;">
                        Toko alat tulis modern
                    </p>

                    <h1 class="fw-bold mb-3" style="font-size:1.65rem; line-height:1.3; color: #212529; max-width: 520px;">
                        Belanja perlengkapan sekolah dan kantor jadi lebih nyaman
                    </h1>

                    <p class="mb-4" style="max-width:500px; font-size:0.92rem; line-height:1.65; color: #6c757d;">
                        Produk lengkap, harga terjangkau, dan katalog yang memudahkan Anda memilih barang
                    </p>

                    <form action="{{ route('public.catalog') }}" method="GET" class="row g-2 mb-4" style="max-width:540px;">
                        <div class="col-12 col-md-8">
                            <input
                                type="text"
                                name="search"
                                class="form-control"
                                style="padding: 0.65rem 1rem; font-size: 0.88rem; border-radius: 0.5rem; border: 1px solid #dee2e6;"
                                placeholder="Cari produk...">
                        </div>
                        <div class="col-12 col-md-4 d-grid">
                            <button class="btn btn-brand" style="padding: 0.65rem 1.2rem; font-size: 0.88rem; font-weight: 500; border-radius: 0.5rem;">Cari</button>
                        </div>
                    </form>

                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('public.catalog') }}" class="btn btn-brand" style="padding: 0.6rem 1.4rem; font-size: 0.88rem; font-weight: 500; border-radius: 0.5rem;">Lihat Katalog</a>
                        <a href="#featured" class="btn btn-outline-dark" style="padding: 0.6rem 1.4rem; font-size: 0.88rem; font-weight: 500; border-radius: 0.5rem;">Produk Unggulan</a>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="hero-art">
                        <div class="bubble one"><i class="bi bi-pencil"></i></div>
                        <div class="bubble two"><i class="bi bi-journal-text"></i></div>
                        <div class="bubble three"><i class="bi bi-briefcase"></i></div>

                        <div class="center-card">
                            <p class="mb-1 fw-semibold text-brand" style="font-size: 0.85rem;">Toko Intan</p>
                            <h5 class="mb-2 brand-serif" style="font-size: 1.1rem;">Premium Stationery</h5>
                            <small class="muted" style="font-size: 0.8rem; color: #6c757d;">Simple, cepat, dan user friendly</small>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div class="container-fluid px-3 px-md-4">
        <section class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="fw-bold mb-0" style="font-size: 1.35rem; color: #212529;">Kenapa Belanja di Sini?</h2>
            </div>

            <div class="row g-3">
                <div class="col-12 col-md-3">
                    <div class="feature-item">
                        <div class="feature-icon" style="font-size: 1.3rem;"><i class="bi bi-tag-fill"></i></div>
                        <h6 class="fw-bold mb-1" style="font-size: 0.95rem;">Harga Kompetitif</h6>
                        <small class="muted" style="font-size: 0.82rem; line-height: 1.5; color: #6c757d;">Harga jelas dan ramah untuk pelajar & kantor</small>
                    </div>
                </div>
                <div class="col-12 col-md-3">
                    <div class="feature-item">
                        <div class="feature-icon" style="font-size: 1.3rem;"><i class="bi bi-box-seam"></i></div>
                        <h6 class="fw-bold mb-1" style="font-size: 0.95rem;">Produk Lengkap</h6>
                        <small class="muted" style="font-size: 0.82rem; line-height: 1.5; color: #6c757d;">Kategori alat tulis bervariasi dan mudah dicari</small>
                    </div>
                </div>
                <div class="col-12 col-md-3">
                    <div class="feature-item">
                        <div class="feature-icon" style="font-size: 1.3rem;"><i class="bi bi-lightning-charge-fill"></i></div>
                        <h6 class="fw-bold mb-1" style="font-size: 0.95rem;">Belanja Cepat</h6>
                        <small class="muted" style="font-size: 0.82rem; line-height: 1.5; color: #6c757d;">Navigasi ringan untuk desktop & mobile</small>
                    </div>
                </div>
                <div class="col-12 col-md-3">
                    <div class="feature-item">
                        <div class="feature-icon" style="font-size: 1.3rem;"><i class="bi bi-shield-check"></i></div>
                        <h6 class="fw-bold mb-1" style="font-size: 0.95rem;">Lebih Aman</h6>
                        <small class="muted" style="font-size: 0.82rem; line-height: 1.5; color: #6c757d;">Info produk rapi dan mudah dipahami</small>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div class="container-fluid px-3 px-md-4">
        <section class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="fw-bold mb-0" style="font-size: 1.35rem; color: #212529;">Kategori Populer</h2>
                <a href="{{ route('public.catalog') }}" class="text-decoration-none text-brand" style="font-size: 0.88rem; font-weight: 500;">Lihat semua →</a>
            </div>

            <div class="d-flex flex-wrap gap-2">
                @forelse($categories as $category)
                <a href="{{ route('public.catalog', ['category' => $category->category_id]) }}" class="chip" style="font-size: 0.85rem; padding: 0.45rem 0.9rem;">
                    <i class="bi bi-folder2-open" style="font-size: 0.9rem;"></i>
                    {{ $category->category_name }}
                    <span class="muted" style="font-size: 0.8rem;">({{ $category->products_count ?? 0 }})</span>
                </a>
                @empty
                <p class="muted mb-0" style="font-size: 0.88rem;">Belum ada kategori.</p>
                @endforelse
            </div>
        </section>
    </div>

    <div class="container-fluid px-3 px-md-4">
        <section id="featured" class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="fw-bold mb-0" style="font-size: 1.35rem; color: #212529;">Produk Unggulan</h2>
                <a href="{{ route('public.catalog') }}" class="text-decoration-none text-brand" style="font-size: 0.88rem; font-weight: 500;">Lihat semua →</a>
            </div>

            <div class="row g-3">
                @forelse($featuredProducts as $product)
                <div class="col-6 col-sm-6 col-md-4 col-lg-3">
                    <x-product-card :product="$product" />
                </div>
                @empty
                <p class="muted mb-0" style="font-size: 0.88rem;">Belum ada produk.</p>
                @endforelse
            </div>
        </section>
    </div>

    <div class="container-fluid px-3 px-md-4">
        <section class="section-card mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="fw-bold mb-0" style="font-size: 1.35rem; color: #212529;">Pertanyaan Umum</h2>
            </div>

            <div class="accordion accordion-flush" id="faqPublic">
                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#faq1" style="font-size: 0.9rem; font-weight: 500;">
                            Apakah bisa lihat produk tanpa login?
                        </button>
                    </h2>
                    <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqPublic">
                        <div class="accordion-body py-3" style="font-size: 0.88rem; color: #6c757d;">Bisa, dashboard dan katalog publik bisa diakses sebelum login.</div>
                    </div>
                </div>

                <div class="accordion-item">
                    <h2 class="accordion-header">
                        <button class="accordion-button collapsed py-3" type="button" data-bs-toggle="collapse" data-bs-target="#faq2" style="font-size: 0.9rem; font-weight: 500;">
                            Bagaimana cara mencari produk?
                        </button>
                    </h2>
                    <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqPublic">
                        <div class="accordion-body py-3" style="font-size: 0.88rem; color: #6c757d;">Gunakan pencarian di hero atau buka katalog lalu filter kategori.</div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>
@endsection