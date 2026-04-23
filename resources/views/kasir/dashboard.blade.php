<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasir - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        :root {
            --primary: #15173D;
            --dark: #0E102A;
            --dark-light: #2A2D66;
            --accent: #15173D;
            --light: #F5F7FF;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background: var(--light);
            color: var(--dark);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', sans-serif;
        }

        .page {
            display: none !important;
        }

        .page.active {
            display: flex !important;
            flex-direction: column;
        }

        /* Navbar */
        .navbar {
            height: 56px;
            background: var(--dark);
            border-bottom: 2px solid var(--accent);
            box-shadow: 0 2px 8px rgba(0, 0, 0, .08);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 24px;
            gap: 20px;
        }

        .navbar a,
        .navbar button {
            text-decoration: none;
        }

        /* Sidebar */
        .sidebar {
            width: 210px;
            background: var(--dark);
            border-right: 2px solid var(--accent);
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            height: 100%;
            overflow-y: auto;
            color: var(--light);
        }

        .sidebar-title {
            padding: 20px 16px 8px;
            font-size: 11px;
            font-weight: 700;
            color: #E8DDD0;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .sidebar-nav {
            flex: 1;
            padding: 4px 10px;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .sidebar-divider {
            margin: 0 14px;
            border-top: 1px solid var(--dark-light);
        }

        .sidebar-footer {
            padding: 14px 16px;
            text-align: center;
        }

        .sidebar-clock {
            font-size: 18px;
            font-weight: 700;
            color: var(--light);
            letter-spacing: .02em;
            line-height: 1.2;
        }

        .sidebar-date {
            font-size: 11px;
            color: #FFFFFF;
            margin-top: 4px;
        }

        /* Nav Links */
        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border-radius: 8px;
            color: #E8DDD0;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all .15s;
            width: 100%;
            text-align: left;
            background: none;
            border: none;
            font-family: inherit;
        }

        .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.14);
            color: #FFFFFF;
        }

        .nav-link.active {
            background-color: #2A2D66;
            color: #FFFFFF;
        }

        .nav-link.active svg {
            stroke: #FFFFFF !important;
        }

        .nav-link svg {
            width: 18px;
            height: 18px;
            stroke: #E8DDD0;
            flex-shrink: 0;
        }

        .nav-desc {
            font-size: 11px;
            color: #F4F4F5;
            display: block;
            margin-top: 1px;
        }

        .nav-link.active .nav-desc {
            color: #FFFFFF;
        }

        /* Brand */
        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
        }

        .brand-logo {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background-color: #FFFFFF;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            color: var(--dark);
        }

        .brand-text {
            line-height: 1.2;
        }

        .brand-name {
            font-weight: 700;
            color: var(--light);
            font-size: 15px;
        }

        .brand-desc {
            font-size: 11px;
            color: #F4F4F5;
        }

        /* Profile Section */
        .profile-section {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            position: relative;
        }

        .profile-avatar {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            background-color: #FFFFFF;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--dark);
            font-weight: 700;
            font-size: 14px;
            flex-shrink: 0;
        }

        .profile-info {
            line-height: 1.2;
            min-width: 0;
        }

        .profile-name {
            font-weight: 600;
            color: var(--light);
            font-size: 13px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .profile-role {
            font-size: 11px;
            color: #F4F4F5;
        }

        /* Dropdown */
        .dropdown-menu {
            display: none;
            position: absolute;
            top: 56px;
            right: 24px;
            background: var(--dark);
            border: 2px solid var(--accent);
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, .15);
            z-index: 50;
            min-width: 160px;
            overflow: hidden;
        }

        .dropdown-menu.show {
            display: block;
        }

        .dropdown-item {
            width: 100%;
            padding: 10px 16px;
            text-align: left;
            background: none;
            border: none;
            color: #ef4444;
            font-size: 14px;
            cursor: pointer;
            font-weight: 500;
            transition: all .2s;
            font-family: inherit;
        }

        .dropdown-item:hover {
            background-color: rgba(255, 255, 255, .1);
        }

        /* Layout */
        .navbar-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
        }

        .navbar-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .menu-toggle {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            border: 1px solid var(--dark-light);
            background: transparent;
            color: var(--light);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background-color .2s;
        }

        .menu-toggle:hover {
            background: rgba(255, 255, 255, .12);
        }

        .navbar-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .navbar-divider {
            width: 1px;
            height: 24px;
            background: var(--dark-light);
        }

        .main-container {
            display: flex;
            height: calc(100vh - 56px);
            overflow: hidden;
            position: relative;
        }

        .content-area {
            flex: 1;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            position: relative;
            background: var(--light);
        }

        .sidebar {
            transition: width .25s ease, min-width .25s ease, opacity .2s ease, transform .25s ease;
        }

        .main-container.sidebar-collapsed .sidebar {
            width: 0;
            min-width: 0;
            opacity: 0;
            pointer-events: none;
            border-right: 0;
        }

        .sidebar-overlay {
            display: none;
        }

        .sidebar-overlay.show {
            display: block;
            position: fixed;
            top: 56px;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, .35);
            z-index: 40;
        }

        @media (max-width: 900px) {
            .sidebar {
                position: fixed;
                top: 56px;
                bottom: 0;
                left: 0;
                z-index: 50;
                width: 240px;
                transform: translateX(-100%);
                opacity: 1;
                pointer-events: auto;
            }

            .main-container.sidebar-open-mobile .sidebar {
                transform: translateX(0);
            }

            .main-container.sidebar-collapsed .sidebar {
                width: 240px;
                min-width: 240px;
                border-right: 2px solid var(--accent);
            }
        }

        .app-modal {
            position: fixed;
            inset: 0;
            z-index: 99999;
            display: flex;
            align-items: center;
            justify-content: center
        }

        .app-modal.is-hidden {
            display: none
        }

        .app-modal__backdrop {
            position: absolute;
            inset: 0;
            background: rgba(15, 23, 42, .45);
            animation: fadeIn .2s ease
        }

        .app-modal__dialog {
            position: relative;
            z-index: 1;
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, .28);
            width: 92vw;
            max-height: 88vh;
            overflow: auto;
            animation: zoomIn .22s ease
        }

        .app-modal__dialog.modal-sm {
            max-width: 420px
        }

        .app-modal__dialog.modal-xl {
            max-width: 980px
        }

        .app-modal__header,
        .app-modal__footer {
            padding: 12px 16px;
            border-bottom: 1px solid #e5e7eb
        }

        .app-modal__footer {
            border-top: 1px solid #e5e7eb;
            border-bottom: none;
            display: flex;
            gap: 8px;
            justify-content: flex-end
        }

        .app-modal__body {
            padding: 16px
        }

        .app-modal__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 16px;
            border-bottom: 1px solid #e5e7eb;
        }

        .app-modal__header h5 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
            line-height: 1.2;
        }

        .app-modal__close {
            margin-left: auto;
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 0;
            background: transparent;
            font-size: 24px;
            line-height: 1;
            cursor: pointer;
            padding: 0;
            color: #334155;
            flex: 0 0 auto;
        }

        @keyframes fadeIn {
            from {
                opacity: 0
            }

            to {
                opacity: 1
            }
        }

        @keyframes zoomIn {
            from {
                opacity: 0;
                transform: translateY(8px) scale(.97)
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1)
            }
        }
    </style>
</head>

<body>

    {{-- ===== NAVBAR ===== --}}
    <nav class="navbar">
        <div class="navbar-left">
            <button type="button" class="menu-toggle" onclick="toggleSidebar()" aria-label="Toggle sidebar">
                <svg style="width:18px; height:18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
            </button>
            <div class="brand">
                <div class="brand-logo">
                    <svg style="width:20px; height:20px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-1.5 6h13M10 21a1 1 0 100-2 1 1 0 000 2zm8 0a1 1 0 100-2 1 1 0 000 2z" />
                    </svg>
                </div>
                <div class="brand-text">
                    <div class="brand-name">TOKO BUKU INTAN</div>
                    <div class="brand-desc">Point of Sale</div>
                </div>
            </div>
        </div>

        <div style="flex:1;"></div>

        <div class="navbar-right">
            {{-- Profile --}}
            <div class="profile-section" onclick="toggleDropdown(event)">
                <div class="profile-avatar">{{ strtoupper(substr(auth()->user()->name ?? 'K', 0, 1)) }}</div>
                <div class="profile-info">
                    <div class="profile-name">{{ auth()->user()->name ?? 'Kasir' }}</div>
                    <div class="profile-role">Kasir</div>
                </div>
                <svg style="width:16px; height:16px; color:#FFFFFF; flex-shrink:0; margin-left:4px;" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                </svg>

                {{-- Dropdown Menu --}}
                <div id="profileDropdown" class="dropdown-menu">
                    <form method="POST" action="{{ route('logout') }}" style="width:100%;">
                        @csrf
                        <button type="submit" class="dropdown-item">Keluar</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    {{-- ===== MAIN AREA ===== --}}
    <div class="main-container">
        @php
        $isWaiter = auth()->user()?->role === 'pelayan';
        @endphp

        {{-- ===== SIDEBAR ===== --}}
        <aside class="sidebar">

            {{-- Menu Label --}}
            <div class="sidebar-title">Menu</div>

            {{-- Navigation --}}
            <nav class="sidebar-nav">

                {{-- Kasir --}}
                <button onclick="showPage('kasir')" id="nav-kasir" class="nav-link active">
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-1.5 6h13M10 21a1 1 0 100-2 1 1 0 000 2zm8 0a1 1 0 100-2 1 1 0 000 2z" />
                    </svg>
                    <div>
                        <span style="display:block; font-size:14px;">Kasir</span>
                        <span class="nav-desc">Daftar produk</span>
                    </div>
                </button>

                {{-- Keranjang --}}
                <button onclick="showPage('keranjang')" id="nav-keranjang" class="nav-link">
                    <div style="position:relative; width:18px; height:18px; flex-shrink:0;">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="2" style="width:18px;height:18px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                        <span id="holdBadge" style="display:none; position:absolute; top:-6px; right:-6px; background:#ef4444; color:white; font-size:10px; font-weight:700; width:16px; height:16px; border-radius:50%; align-items:center; justify-content:center;">0</span>
                    </div>
                    <div style="flex:1;">
                        <span style="display:block; font-size:14px;">Hold Order</span>
                        <span class="nav-desc">Pesanan tertahan</span>
                    </div>
                    <span id="holdBadgeSidebar" style="display:none; background:#ef4444; color:white; font-size:10px; font-weight:700; padding:1px 6px; border-radius:999px;">0</span>
                </button>

                @if (! $isWaiter)
                <button onclick="showPage('pending')" id="nav-pending" class="nav-link">
                    <div style="position:relative; width:18px; height:18px; flex-shrink:0;">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="2" style="width:18px;height:18px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span id="pendingBadge" style="display:none; position:absolute; top:-6px; right:-6px; background:#ef4444; color:white; font-size:10px; font-weight:700; width:16px; height:16px; border-radius:50%; align-items:center; justify-content:center;">0</span>
                    </div>
                    <div style="flex:1;">
                        <span style="display:block; font-size:14px;">Waiting Payment</span>
                        <span class="nav-desc">Antrian pembayaran</span>
                    </div>
                    <span id="pendingBadgeSidebar" style="display:none; background:#ef4444; color:white; font-size:10px; font-weight:700; padding:1px 6px; border-radius:999px;">0</span>
                </button>
                @endif

                {{-- Transaksi --}}
                @if (! $isWaiter)
                <button onclick="showPage('transaksi')" id="nav-transaksi" class="nav-link">
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                    </svg>
                    <div>
                        <span style="display:block; font-size:14px;">Pembayaran</span>
                        <span class="nav-desc">Proses transaksi</span>
                    </div>
                </button>
                @endif

            </nav>

            {{-- Divider --}}
            <div class="sidebar-divider"></div>

            {{-- Clock --}}
            <div class="sidebar-footer">
                <div class="sidebar-clock" id="sidebar-time"></div>
                <div class="sidebar-date" id="sidebar-date"></div>
            </div>

            {{-- Divider --}}
            <div class="sidebar-divider"></div>
        </aside>

        {{-- ===== CONTENT AREA ===== --}}
        <div class="content-area">
            @include('kasir.pages.kasir')
            @include('kasir.pages.keranjang')
            @unless ($isWaiter)
            @include('kasir.pages.pending')
            @include('kasir.pages.transaksi')
            @endunless
        </div>

        <div id="sidebarOverlay" class="sidebar-overlay" onclick="closeMobileSidebar()"></div>

    </div>

    <div id="appModal" class="app-modal is-hidden" aria-hidden="true">
        <div class="app-modal__backdrop" onclick="closeAppModal()"></div>
        <div id="appModalDialog" class="app-modal__dialog modal-sm">
            <div class="app-modal__header">
                <h5 id="appModalTitle">Informasi</h5>
                <button type="button" class="app-modal__close" onclick="closeAppModal()">×</button>
            </div>
            <div id="appModalBody" class="app-modal__body"></div>
            <div class="app-modal__footer">
                <button id="appModalCancel" type="button" class="btn btn-light" style="display:none;">Batal</button>
                <button id="appModalOk" type="button" class="btn btn-primary">OK</button>
            </div>
        </div>
    </div>

    @include('kasir.scripts.kasir')
    @include('kasir.scripts.keranjang')
    @unless ($isWaiter)
    @include('kasir.scripts.pending')
    @include('kasir.scripts.transaksi')
    @endunless

    <script>
        function toggleDropdown(e) {
            e.stopPropagation();
            const dropdown = document.getElementById('profileDropdown');
            dropdown.classList.toggle('show');
        }

        document.addEventListener('click', function() {
            document.getElementById('profileDropdown').classList.remove('show');
        });

        function showPage(page) {
            document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
            document.querySelectorAll('.nav-link').forEach(b => b.classList.remove('active'));

            const pageEl = document.getElementById('page-' + page);
            const navEl = document.getElementById('nav-' + page);

            if (pageEl) pageEl.classList.add('active');
            if (navEl) navEl.classList.add('active');

            // Render berdasarkan page yang dituju
            if (page === 'keranjang' && typeof renderCart === 'function') {
                renderCart();
            }
            if (page === 'pending' && typeof renderPendingList === 'function') {
                renderPendingList();
            }
            if (page === 'transaksi') {
                if (typeof renderOrderSummary === 'function') {
                    renderOrderSummary();
                }
            }
            if (window.matchMedia('(max-width: 900px)').matches) {
                closeMobileSidebar();
            }
        }

        function toggleSidebar() {
            const mainContainer = document.querySelector('.main-container');
            const overlay = document.getElementById('sidebarOverlay');
            const isMobile = window.matchMedia('(max-width: 900px)').matches;

            if (!mainContainer) return;

            if (isMobile) {
                mainContainer.classList.toggle('sidebar-open-mobile');
                overlay.classList.toggle('show', mainContainer.classList.contains('sidebar-open-mobile'));
                return;
            }

            mainContainer.classList.toggle('sidebar-collapsed');
        }

        function closeMobileSidebar() {
            const mainContainer = document.querySelector('.main-container');
            const overlay = document.getElementById('sidebarOverlay');
            if (!mainContainer) return;
            mainContainer.classList.remove('sidebar-open-mobile');
            if (overlay) overlay.classList.remove('show');
        }

        window.addEventListener('resize', function() {
            if (!window.matchMedia('(max-width: 900px)').matches) {
                closeMobileSidebar();
            }
        });

        function updateClock() {
            const now = new Date();
            const t = document.getElementById('sidebar-time');
            const d = document.getElementById('sidebar-date');
            if (t) t.innerText = now.toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
            if (d) d.innerText = now.toLocaleDateString('id-ID', {
                weekday: 'long',
                day: 'numeric',
                month: 'long',
                year: 'numeric'
            });
        }

        function closeAppModal() {
            const el = document.getElementById('appModal');
            if (!el) return;
            el.classList.add('is-hidden');
            el.setAttribute('aria-hidden', 'true');
        }

        function openAppModal(opts = {}) {
            const cfg = Object.assign({
                size: 'modal-sm',
                title: 'Informasi',
                message: '',
                okText: 'OK',
                cancelText: 'Batal',
                showCancel: false,
                onOk: null,
                onCancel: null
            }, opts);

            const modal = document.getElementById('appModal');
            const dialog = document.getElementById('appModalDialog');
            const title = document.getElementById('appModalTitle');
            const body = document.getElementById('appModalBody');
            const ok = document.getElementById('appModalOk');
            const cancel = document.getElementById('appModalCancel');
            if (!modal || !dialog || !title || !body || !ok || !cancel) return;

            dialog.classList.remove('modal-sm', 'modal-xl');
            dialog.classList.add(cfg.size === 'modal-xl' ? 'modal-xl' : 'modal-sm');

            title.textContent = cfg.title;
            body.innerHTML = cfg.message;
            ok.textContent = cfg.okText;
            cancel.textContent = cfg.cancelText;
            cancel.style.display = cfg.showCancel ? 'inline-block' : 'none';

            ok.onclick = () => {
                closeAppModal();
                if (typeof cfg.onOk === 'function') cfg.onOk();
            };
            cancel.onclick = () => {
                closeAppModal();
                if (typeof cfg.onCancel === 'function') cfg.onCancel();
            };

            modal.classList.remove('is-hidden');
            modal.setAttribute('aria-hidden', 'false');
        }

        window.showAlert = function(message, type = 'info') {
            const map = {
                success: 'Berhasil',
                error: 'Error',
                warning: 'Peringatan',
                info: 'Informasi'
            };
            openAppModal({
                title: map[type] || 'Informasi',
                message: message || '-',
                okText: 'OK'
            });
        };

        window.showConfirm = function(message, onOk, onCancel = null, size = 'modal-sm') {
            openAppModal({
                size,
                title: 'Konfirmasi',
                message,
                okText: 'Ya',
                cancelText: 'Batal',
                showCancel: true,
                onOk,
                onCancel
            });
        };

        updateClock();
        setInterval(updateClock, 1000);
        showPage('kasir');
        if (typeof renderPendingList === 'function') {
            renderPendingList();
        }
    </script>

</body>

</html>