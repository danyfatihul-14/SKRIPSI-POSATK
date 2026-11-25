<!doctype html>
<html lang="id">

<head>
    <link rel="icon" type="image/png" href="{{ asset('images/4.png') }}">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - TOKO KURNIA</title>
    <style>
        :root {
            --ink: #1f2428;
            --sun: #f59e0b;
            --sun-deep: #d97706;
            --sand: #fff1d6;
            --mint: #dff3ee;
            --mist: #f7f4ef;
            --stone: #ece6db;
            --ring: rgba(245, 158, 11, 0.35);
            --shadow: 0 20px 60px rgba(20, 20, 10, 0.12);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Avenir Next", "Trebuchet MS", "Segoe UI", sans-serif;
            color: var(--ink);
            background:
                radial-gradient(800px 400px at 10% 10%, var(--sand), transparent 60%),
                radial-gradient(700px 380px at 90% 20%, var(--mint), transparent 55%),
                linear-gradient(180deg, #fbf8f2 0%, #f3eee6 100%);
            min-height: 100vh;
        }

        .wrap {
            min-height: 100vh;
            display: grid;
            place-items: center;
            padding: 32px 16px;
        }

        .card {
            width: min(920px, 100%);
            display: grid;
            grid-template-columns: 1.1fr 1fr;
            border-radius: 20px;
            overflow: hidden;
            background: #fff;
            box-shadow: var(--shadow);
            border: 1px solid var(--stone);
        }

        .brand {
            padding: 40px 36px;
            background:
                linear-gradient(135deg, rgba(245, 158, 11, 0.12), transparent 60%),
                linear-gradient(0deg, #fff, #fff);
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            padding: 6px 12px;
            border-radius: 999px;
            background: #fff2c6;
            color: #8a5a00;
            border: 1px solid #f5d58f;
            width: fit-content;
        }

        .brand h1 {
            margin: 0;
            font-size: 30px;
            line-height: 1.15;
            letter-spacing: -0.02em;
            color: #1e293b;
        }

        .brand p {
            margin: 0;
            color: #64748b;
            font-size: 14px;
            line-height: 1.5;
        }

        .stripe {
            margin-top: 8px;
            height: 6px;
            width: 120px;
            border-radius: 999px;
            background: linear-gradient(90deg, var(--sun), #ffd48a);
        }

        .feature {
            display: grid;
            grid-template-columns: 20px 1fr;
            gap: 12px;
            font-size: 13px;
            color: #475569;
        }

        .feature span {
            display: inline-block;
            height: 10px;
            width: 10px;
            border-radius: 999px;
            background: var(--sun);
            margin-top: 4px;
        }

        .form {
            padding: 40px 36px;
            background: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .form h2 {
            margin: 0 0 6px;
            font-size: 22px;
            color: #0f172a;
        }

        .form .sub {
            margin: 0 0 20px;
            font-size: 13px;
            color: #64748b;
        }

        .err {
            background: #fff1f2;
            color: #9f1239;
            border: 1px solid #fecdd3;
            padding: 10px 14px;
            border-radius: 10px;
            margin-bottom: 16px;
            font-size: 13px;
            font-weight: 600;
        }

        label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.05em;
            color: #475569;
            margin: 14px 0 6px;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px 14px;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            background: #f8fafc;
            font-size: 14px;
            color: #0f172a;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: var(--sun);
            background: #fff;
            box-shadow: 0 0 0 4px var(--ring);
        }

        .password-wrap {
            position: relative;
        }

        .password-wrap input {
            padding-right: 44px;
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            border: 0;
            background: transparent;
            color: #94a3b8;
            cursor: pointer;
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            transition: color 0.2s;
        }

        .toggle-password:hover {
            color: #475569;
            background: #f1f5f9;
        }

        .toggle-password svg {
            width: 20px;
            height: 20px;
        }

        .hidden {
            display: none !important;
        }

        .row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 16px;
            font-size: 13px;
            color: #64748b;
        }

        .row label {
            margin: 0;
            font-weight: 600;
            letter-spacing: 0;
            display: flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
        }

        .btn {
            width: 100%;
            margin-top: 22px;
            background: linear-gradient(180deg, var(--sun), var(--sun-deep));
            color: #fff;
            border: 0;
            padding: 13px 16px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 12px 24px rgba(245, 158, 11, 0.25);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 16px 28px rgba(245, 158, 11, 0.35);
        }

        .foot {
            margin-top: 18px;
            font-size: 12px;
            color: #94a3b8;
            text-align: center;
        }

        @media (max-width: 840px) {
            .card {
                grid-template-columns: 1fr;
            }

            .brand {
                padding: 28px 24px;
            }

            .form {
                padding: 28px 24px;
            }
        }
    </style>
</head>

<body>
    <div class="wrap">
        <div class="card">
            <section class="brand">
                <span class="badge">TOKO KURNIA</span>
                <h1>Masuk untuk kelola transaksi dengan cepat</h1>
                <p>Panel kasir dan admin dipisah otomatis sesuai role akun.</p>
                <div class="stripe"></div>

                <div class="feature">
                    <span></span>
                    <div>Kasir diarahkan ke halaman transaksi.</div>
                </div>
                <div class="feature">
                    <span></span>
                    <div>Owner langsung ke dashboard admin.</div>
                </div>
                <div class="feature">
                    <span></span>
                    <div>Antarmuka ringan, siap operasional harian.</div>
                </div>
            </section>

            <section class="form">
                <h2>Login</h2>
                <p class="sub">Gunakan username dan password yang terdaftar.</p>

                @if ($errors->any())
                <div class="err">
                    {{ $errors->first() }}
                </div>
                @endif

                <form method="POST" action="{{ route('login.store') }}">
                    @csrf

                    <label for="username">USERNAME</label>
                    <input id="username" name="username" type="text" value="{{ old('username') }}" placeholder="Masukkan username" required autofocus>

                    <label for="password">PASSWORD</label>
                    <div class="password-wrap">
                        <input id="password" name="password" type="password" placeholder="Masukkan password" required>
                        <button type="button" id="togglePassword" class="toggle-password" aria-label="Tampilkan password">
                            {{-- eye (dibuka) --}}
                            <svg id="iconEye" class="hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>

                            {{-- eye-off (tertutup) --}}
                            <svg id="iconEyeOff" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13.875 18.825A10.05 10.05 0 0112 19c-4.477 0-8.268-2.943-9.542-7a10.05 10.05 0 012.722-4.144M6.343 6.343A9.96 9.96 0 0112 5c4.477 0 8.268 2.943 9.542 7a9.97 9.97 0 01-4.132 5.411m0 0L21 21M3 3l18 18" />
                            </svg>
                        </button>
                    </div>

                    <div class="row">
                        <label class="remember">
                            <input type="checkbox" name="remember">
                            <span>Ingat saya</span>
                        </label>
                    </div>

                    <button type="submit" class="btn">Masuk ke Sistem</button>
                </form>

                <div class="foot">TOKO KURNIA • Sistem POS</div>
            </section>
        </div>
    </div>

    <script>
        const passwordInput = document.getElementById('password');
        const togglePassword = document.getElementById('togglePassword');
        const iconEye = document.getElementById('iconEye');
        const iconEyeOff = document.getElementById('iconEyeOff');

        if (togglePassword && passwordInput) {
            togglePassword.addEventListener('click', function() {
                const isHidden = passwordInput.type === 'password';

                if (isHidden) {
                    passwordInput.type = 'text';
                    iconEye.classList.remove('hidden');
                    iconEyeOff.classList.add('hidden');
                    togglePassword.setAttribute('aria-label', 'Sembunyikan password');
                } else {
                    passwordInput.type = 'password';
                    iconEye.classList.add('hidden');
                    iconEyeOff.classList.remove('hidden');
                    togglePassword.setAttribute('aria-label', 'Tampilkan password');
                }
            });
        }
    </script>
</body>

</html>