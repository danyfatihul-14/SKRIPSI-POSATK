<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Toko Intan')</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <style>
        :root {
            --brand: #d97706;
            --brand-2: #f59e0b;
            --cream: #fff7ed;
            --paper: #fffbf5;
            --ink: #2b2118;
            --muted: #7a6757;
            --line: #f0decf;
        }

        body {
            font-family: "Plus Jakarta Sans", sans-serif;
            background:
                radial-gradient(900px 420px at 8% -10%, #ffe3c2 0%, rgba(255, 227, 194, 0) 55%),
                radial-gradient(900px 420px at 100% -15%, #fff0dc 0%, rgba(255, 240, 220, 0) 52%),
                var(--cream);
            color: var(--ink);
            font-size: 14px;
            min-height: 100vh;
        }

        h1,
        h2,
        h3,
        .brand-serif {
            font-family: "Playfair Display", serif;
            letter-spacing: .01em;
        }

        .container {
            max-width: 1080px;
        }

        .glass-nav {
            background: rgba(255, 251, 245, .88);
            backdrop-filter: blur(8px);
            border-bottom: 1px solid var(--line);
        }

        .hero-shell {
            background: var(--paper);
            border: 1px solid var(--line);
            border-radius: 18px;
            padding: 24px;
            box-shadow: 0 10px 30px rgba(89, 54, 24, .06);
        }

        .hero-art {
            border-radius: 16px;
            border: 1px solid var(--line);
            background:
                radial-gradient(circle at 70% 25%, #ffd7a8 0%, transparent 38%),
                radial-gradient(circle at 25% 75%, #ffe8ca 0%, transparent 40%),
                linear-gradient(145deg, #fff7eb 0%, #fff2df 100%);
            min-height: 280px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .hero-art .bubble {
            width: 78px;
            height: 78px;
            border-radius: 999px;
            border: 1px solid #f4d9bf;
            background: #fff8ee;
            display: grid;
            place-items: center;
            color: var(--brand);
            font-size: 1.4rem;
            box-shadow: 0 8px 18px rgba(120, 68, 20, .08);
        }

        .hero-art .bubble.one {
            position: absolute;
            top: 24px;
            left: 22px;
        }

        .hero-art .bubble.two {
            position: absolute;
            top: 92px;
            right: 20px;
        }

        .hero-art .bubble.three {
            position: absolute;
            bottom: 20px;
            left: 76px;
        }

        .hero-art .center-card {
            border: 1px solid var(--line);
            border-radius: 14px;
            background: #fffdf9;
            padding: 18px;
            width: 80%;
            text-align: center;
        }

        .chip {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            padding: .45rem .8rem;
            border-radius: 999px;
            border: 1px solid #edd8c3;
            background: #fff9f1;
            color: #5d4129;
            text-decoration: none;
            font-size: .82rem;
            font-weight: 600;
            transition: all .2s ease;
        }

        .chip:hover {
            border-color: #d8ab79;
            color: #4f351f;
            transform: translateY(-1px);
        }

        .feature-item {
            border: 1px solid var(--line);
            border-radius: 12px;
            background: #fff;
            padding: 14px;
            height: 100%;
        }

        .feature-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            background: #fff3e2;
            color: var(--brand);
            display: grid;
            place-items: center;
            margin-bottom: 8px;
            font-size: 1rem;
        }

        .section-card {
            border: 1px solid var(--line);
            border-radius: 14px;
            background: #fffdfa;
            padding: 14px;
        }

        .btn-brand {
            background: var(--brand);
            border-color: var(--brand);
            color: #fff;
        }

        .btn-brand:hover {
            background: #b96305;
            border-color: #b96305;
            color: #fff;
        }

        .text-brand {
            color: var(--brand) !important;
        }

        .muted {
            color: var(--muted);
        }

        .product-card {
            border: 1px solid var(--line);
            border-radius: 14px;
            overflow: hidden;
            background: #fff;
            height: 100%;
            max-width: 250px;
            margin: 0 auto;
        }

        .product-thumb {
            width: 100%;
            height: 160px;
            object-fit: contain;
            background: #fffaf3;
        }
    </style>
</head>

<body>
    <x-navbar />

    <main class="container py-4 py-lg-5">
        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>