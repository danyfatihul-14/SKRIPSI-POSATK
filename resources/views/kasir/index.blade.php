{{-- filepath: resources/views/kasir/index.blade.php --}}
<style>
    .cashier-layout {
        display: flex;
        width: 100%;
        min-height: 100vh;
        overflow: hidden;
        position: relative;
    }

    .cashier-sidebar {
        width: 16rem;
        transition: width .25s ease, min-width .25s ease, opacity .2s ease, transform .25s ease;
    }

    .cashier-content {
        flex: 1;
        overflow: hidden;
        position: relative;
        display: flex;
        flex-direction: column;
    }

    .cashier-layout.sidebar-collapsed .cashier-sidebar {
        width: 0;
        min-width: 0;
        opacity: 0;
        pointer-events: none;
    }

    .cashier-menu-toggle {
        position: absolute;
        top: 12px;
        left: 12px;
        z-index: 30;
        width: 36px;
        height: 36px;
        border-radius: 10px;
        border: 1px solid rgba(255, 255, 255, .2);
        background: rgba(17, 24, 39, .85);
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
    }

    .cashier-overlay {
        display: none;
    }

    .cashier-overlay.show {
        display: block;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, .35);
        z-index: 25;
    }

    @media (max-width: 1024px) {
        .cashier-sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 35;
            transform: translateX(-100%);
            width: 16rem;
            opacity: 1;
            pointer-events: auto;
        }

        .cashier-layout.sidebar-open-mobile .cashier-sidebar {
            transform: translateX(0);
        }

        .cashier-layout.sidebar-collapsed .cashier-sidebar {
            width: 16rem;
            min-width: 16rem;
            opacity: 1;
            pointer-events: auto;
        }
    }
</style>

<div id="cashierLayout" class="cashier-layout">
    @php
    $isWaiter = auth()->user()?->role === 'pelayan';
    @endphp
    {{-- SIDEBAR --}}
    <aside id="cashierSidebar" class="w-64 sidebar-gradient flex flex-col shadow-2xl flex-shrink-0 cashier-sidebar">

        {{-- Brand --}}
        <div class="px-6 py-5 border-b border-white/10">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 accent-bg rounded-xl flex items-center justify-center shadow-lg">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-1.5 6h13M10 21a1 1 0 100-2 1 1 0 000 2zm8 0a1 1 0 100-2 1 1 0 000 2z" />
                    </svg>
                </div>
                <div>
                    <div class="text-white font-bold text-base leading-tight">{{ config('app.name') }}</div>
                    <div class="text-xs opacity-60 text-white">Point of Sale</div>
                </div>
            </div>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 px-4 py-4 space-y-1">
            <p class="text-white/30 text-xs font-semibold uppercase tracking-wider px-3 mb-3">Menu</p>

            {{-- Kasir --}}
            <button onclick="showPage('kasir')" id="nav-kasir"
                class="nav-btn w-full flex items-center gap-3 px-4 py-3 rounded-xl text-white/50 hover:bg-white/10 hover:text-white transition-all duration-200 group">
                <div class="nav-icon w-9 h-9 rounded-lg bg-white/10 group-hover:bg-white/20 flex items-center justify-center transition-all duration-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-1.5 6h13M10 21a1 1 0 100-2 1 1 0 000 2zm8 0a1 1 0 100-2 1 1 0 000 2z" />
                    </svg>
                </div>
                <div class="text-left">
                    <div class="text-sm font-semibold text-white">Kasir</div>
                    <div class="nav-sub text-xs text-white/40">Daftar produk</div>
                </div>
            </button>

            {{-- Keranjang --}}
            <button onclick="showPage('keranjang')" id="nav-keranjang"
                class="nav-btn w-full flex items-center gap-3 px-4 py-3 rounded-xl text-white/50 hover:bg-white/10 hover:text-white transition-all duration-200 group">
                <div class="nav-icon w-9 h-9 rounded-lg bg-white/10 group-hover:bg-white/20 flex items-center justify-center transition-all duration-200 relative">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                    </svg>
                    <span id="cartBadge"
                        class="hidden absolute -top-1.5 -right-1.5 bg-red-500 text-white text-[10px] font-bold w-4 h-4 rounded-full flex items-center justify-center">0</span>
                </div>
                <div class="text-left flex-1">
                    <div class="text-sm font-semibold text-white">Keranjang</div>
                    <div class="nav-sub text-xs text-white/40">Item belanja</div>
                </div>
                <span id="cartBadgeSidebar" class="hidden bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full">0</span>
            </button>

            {{-- Transaksi --}}
            @if (! $isWaiter)
            <button onclick="showPage('transaksi')" id="nav-transaksi"
                class="nav-btn w-full flex items-center gap-3 px-4 py-3 rounded-xl text-white/50 hover:bg-white/10 hover:text-white transition-all duration-200 group">
                <div class="nav-icon w-9 h-9 rounded-lg bg-white/10 group-hover:bg-white/20 flex items-center justify-center transition-all duration-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                </div>
                <div class="text-left">
                    <div class="text-sm font-semibold text-white">Pembayaran</div>
                    <div class="nav-sub text-xs text-white/40">Proses transaksi</div>
                </div>
            </button>
            @endif

        </nav>

        {{-- Footer Sidebar --}}
        <div class="px-4 py-4 border-t border-white/10">
            <div class="px-4 py-3 bg-white/10 rounded-xl mb-3 text-center">
                <div id="sidebar-time" class="text-white font-bold text-base"></div>
                <div id="sidebar-date" class="text-white/40 text-xs mt-0.5"></div>
            </div>
        </div>

    </aside>

    {{-- KONTEN --}}
    <div class="flex-1 overflow-hidden relative flex flex-col cashier-content">
        <button type="button" class="cashier-menu-toggle" onclick="toggleCashierSidebar()" aria-label="Toggle sidebar">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>

        @include('kasir.pages.kasir')
        @include('kasir.pages.keranjang')
        @unless ($isWaiter)
        @include('kasir.scripts.pending')
        @include('kasir.pages.transaksi')
        @endunless

    </div>

    <div id="cashierSidebarOverlay" class="cashier-overlay" onclick="closeCashierSidebar()"></div>
</div>

@push('scripts')
<script>
    function toggleCashierSidebar() {
        const layout = document.getElementById('cashierLayout');
        const overlay = document.getElementById('cashierSidebarOverlay');
        const isMobile = window.matchMedia('(max-width: 1024px)').matches;
        if (!layout) return;

        if (isMobile) {
            layout.classList.toggle('sidebar-open-mobile');
            if (overlay) overlay.classList.toggle('show', layout.classList.contains('sidebar-open-mobile'));
            return;
        }

        layout.classList.toggle('sidebar-collapsed');
    }

    function closeCashierSidebar() {
        const layout = document.getElementById('cashierLayout');
        const overlay = document.getElementById('cashierSidebarOverlay');
        if (!layout) return;
        layout.classList.remove('sidebar-open-mobile');
        if (overlay) overlay.classList.remove('show');
    }

    const existingShowPage = window.showPage;
    window.showPage = function(page) {
        if (typeof existingShowPage === 'function') {
            existingShowPage(page);
        } else {
            document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
            document.querySelectorAll('[id^="nav-"]').forEach(b => b.classList.remove('active'));

            const pageEl = document.getElementById('page-' + page);
            const navEl = document.getElementById('nav-' + page);

            if (pageEl) pageEl.classList.add('active');
            if (navEl) navEl.classList.add('active');

            if (page === 'keranjang' && typeof renderCart === 'function') {
                renderCart();
            }
            if (page === 'transaksi' && typeof renderOrderSummary === 'function') {
                renderOrderSummary();
            }
        }

        if (window.matchMedia('(max-width: 1024px)').matches) {
            closeCashierSidebar();
        }
    };

    window.addEventListener('resize', function() {
        if (!window.matchMedia('(max-width: 1024px)').matches) {
            closeCashierSidebar();
        }
    });

    function updateClock() {
        const now = new Date();
        document.getElementById('sidebar-time').innerText =
            now.toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        document.getElementById('sidebar-date').innerText =
            now.toLocaleDateString('id-ID', {
                weekday: 'long',
                day: 'numeric',
                month: 'long',
                year: 'numeric'
            });
    }
    updateClock();
    setInterval(updateClock, 1000);
</script>
@include('kasir.scripts.kasir')
@include('kasir.scripts.keranjang')
@unless ($isWaiter)
@include('kasir.scripts.transaksi')
@endunless