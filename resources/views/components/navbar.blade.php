<nav class="navbar navbar-expand-lg sticky-top glass-nav py-2">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="{{ route('public.dashboard') }}">
            <img
                src="{{ asset('images/logo-intan.png') }}"
                alt="Logo Toko Intan"
                style="height:34px; width:auto; border-radius:8px;">
            <span class="brand-serif fw-bold" style="font-size:1.15rem; color:#4c311f;">Toko Intan</span>
        </a>

        <button class="navbar-toggler py-1 px-2" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2 mt-2 mt-lg-0">
                <li class="nav-item">
                    <a class="nav-link fw-semibold {{ request()->routeIs('public.dashboard') ? 'text-brand' : 'text-dark' }}"
                        href="{{ route('public.dashboard') }}">
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-semibold {{ request()->routeIs('public.catalog') || request()->routeIs('public.product.show') ? 'text-brand' : 'text-dark' }}"
                        href="{{ route('public.catalog') }}">
                        Catalog Produk
                    </a>
                </li>
                <li class="nav-item ms-lg-2">
                    <a href="{{ route('login') }}" class="btn btn-sm btn-dark rounded-pill px-3">Login</a>
                </li>
            </ul>
        </div>
    </div>
</nav>