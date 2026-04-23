<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasir - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    <style>
        :root {
            --accent: #16a34a;
        }

        body.theme-green {
            --accent: #16a34a;
        }

        body.theme-blue {
            --accent: #2563eb;
        }

        body.theme-purple {
            --accent: #7c3aed;
        }

        body.theme-orange {
            --accent: #ea580c;
        }

        body.theme-red {
            --accent: #dc2626;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        .page {
            display: none !important;
        }

        .page.active {
            display: flex !important;
            flex-direction: column;
        }

        /* Sidebar Nav */
        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 12px;
            border-radius: 8px;
            color: #374151;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.15s ease;
            width: 100%;
            text-align: left;
            background: none;
            border: none;
            font-family: inherit;
        }

        .nav-link:hover {
            background-color: #f3f4f6;
        }

        .nav-link.active {
            background-color: var(--accent);
            color: white;
        }

        .nav-link.active svg {
            stroke: white !important;
        }

        .nav-link.active .nav-desc {
            color: rgba(255, 255, 255, 0.75);
        }

        .nav-link svg {
            width: 18px;
            height: 18px;
            stroke: #6b7280;
            flex-shrink: 0;
        }

        .nav-desc {
            font-size: 11px;
            color: #9ca3af;
            display: block;
            line-height: 1.2;
            margin-top: 1px;
        }

        .accent-bg {
            background-color: var(--accent) !important;
        }

        .accent-txt {
            color: var(--accent) !important;
        }

        .theme-dot {
            width: 18px;
            height: 18px;
            border-radius: 50%;
            border: 2px solid transparent;
            cursor: pointer;
            transition: all 0.2s;
            outline: none;
        }

        .theme-dot.active {
            border-color: #1f2937;
            transform: scale(1.25);
        }
    </style>
</head>

<body class="theme-green" id="appBody" style="height:100vh; overflow:hidden; background:#f3f4f6;">

    {{-- ===== NAVBAR ===== --}}
    <nav style="height:56px; background:white; border-bottom:1px solid #e5e7eb; box-shadow:0 1px 3px rgba(0,0,0,.06);"
        class="flex items-center justify-between px-6">

        {{-- Brand --}}
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 rounded-lg accent-bg flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-1.5 6h13M10 21a1 1 0 100-2 1 1 0 000 2zm8 0a1 1 0 100-2 1 1 0 000 2z" />
                </svg>
            </div>
            <div>
                <div class="font-bold text-gray-800" style="font-size:15px; line-height:1.2">{{ config('app.name') }}</div>
                <div class="text-gray-400" style="font-size:11px">Point of Sale</div>
            </div>
        </div>

        {{-- Kanan --}}
        <div class="flex items-center gap-4">

            {{-- Theme Picker --}}
            <div class="flex items-center gap-2" style="background:#f9fafb; border:1px solid #e5e7eb; border-radius:10px; padding:6px 12px;">
                <span style="font-size:11px; color:#9ca3af; font-weight:500; margin-right:4px;">Tema:</span>
                <button onclick="setTheme('green')" data-theme="green" class="theme-dot" style="background:#16a34a" title="Hijau"></button>
                <button onclick="setTheme('blue')" data-theme="blue" class="theme-dot" style="background:#2563eb" title="Biru"></button>
                <button onclick="setTheme('purple')" data-theme="purple" class="theme-dot" style="background:#7c3aed" title="Ungu"></button>
                <button onclick="setTheme('orange')" data-theme="orange" class="theme-dot" style="background:#ea580c" title="Orange"></button>
                <button onclick="setTheme('red')" data-theme="red" class="theme-dot" style="background:#dc2626" title="Merah"></button>
            </div>

            <div style="width:1px; height:24px; background:#e5e7eb;"></div>

            {{-- User --}}
            <div class="flex items-center gap-2">
                <div class="accent-bg flex items-center justify-center text-white font-bold flex-shrink-0"
                    style="width:34px; height:34px; border-radius:50%; font-size:14px;">
                    {{ strtoupper(substr(auth()->user()->name ?? 'K', 0, 1)) }}
                </div>
                <div>
                    <div class="font-semibold text-gray-800" style="font-size:14px; line-height:1.2">
                        {{ auth()->user()->name ?? 'Kasir' }}
                    </div>
                    <div class="text-gray-400" style="font-size:11px">Kasir</div>
                </div>
            </div>

        </div>
    </nav>

    {{-- ===== MAIN ===== --}}
    <div style="display:flex; height:calc(100vh - 56px); overflow:hidden;">
        {{ $slot }}
    </div>

    @stack('scripts')

    <script>
        function setTheme(theme) {
            const body = document.getElementById('appBody');
            ['green', 'blue', 'purple', 'orange', 'red'].forEach(t => body.classList.remove('theme-' + t));
            body.classList.add('theme-' + theme);
            localStorage.setItem('kasir-theme', theme);

            const colors = {
                green: '#16a34a',
                blue: '#2563eb',
                purple: '#7c3aed',
                orange: '#ea580c',
                red: '#dc2626'
            };
            const color = colors[theme];

            document.documentElement.style.setProperty('--accent', color);
            document.querySelectorAll('.accent-bg').forEach(el => el.style.backgroundColor = color);
            document.querySelectorAll('.accent-txt').forEach(el => el.style.color = color);

            // Update active nav warna
            document.querySelectorAll('.nav-link.active').forEach(el => el.style.backgroundColor = color);

            document.querySelectorAll('.theme-dot').forEach(d => {
                d.classList.remove('active');
                if (d.dataset.theme === theme) d.classList.add('active');
            });
        }

        const savedTheme = localStorage.getItem('kasir-theme') || 'green';
        setTheme(savedTheme);
    </script>

</body>

</html>