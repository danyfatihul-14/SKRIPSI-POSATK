{{-- filepath: e:\Polinema\Semester8\Skripsi\pos\resources\views\auth\login.blade.php --}}
<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - TOKO INTAN</title>
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
            width: min(980px, 100%);
            display: grid;
            grid-template-columns: 1.1fr 1fr;
            border-radius: 20px;
            overflow: hidden;
            background: #fff;
            box-shadow: var(--shadow);
            border: 1px solid var(--stone);
        }

        .brand {
            padding: 36px 32px;
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
            padding: 6px 10px;
            border-radius: 999px;
            background: #fff2c6;
            color: #8a5a00;
            border: 1px solid #f5d58f;
            width: fit-content;
        }

        .brand h1 {
            margin: 0;
            font-size: 32px;
            line-height: 1.05;
            letter-spacing: -0.02em;
        }

        .brand p {
            margin: 0;
            color: #5a5a5a;
            font-size: 14px;
            max-width: 32ch;
        }

        .stripe {
            margin-top: 12px;
            height: 8px;
            width: 140px;
            border-radius: 999px;
            background: linear-gradient(90deg, var(--sun), #ffd48a);
        }

        .feature {
            display: grid;
            grid-template-columns: 20px 1fr;
            gap: 12px;
            font-size: 13px;
            color: #5a5a5a;
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
            padding: 36px 32px;
            background: #fff;
        }

        .form h2 {
            margin: 0 0 8px;
            font-size: 20px;
        }

        .form .sub {
            margin: 0 0 20px;
            font-size: 13px;
            color: #777;
        }

        .err {
            background: #fff1f2;
            color: #9f1239;
            border: 1px solid #fecdd3;
            padding: 10px 12px;
            border-radius: 10px;
            margin-bottom: 14px;
            font-size: 13px;
        }

        label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.03em;
            margin: 12px 0 6px;
        }

        input[type="text"],
        input[type="password"],
        input[type="text"].password-input {
            width: 100%;
            padding: 12px 12px;
            border: 1px solid #e3e0da;
            border-radius: 10px;
            background: #fcfbf9;
            font-size: 14px;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        input[type="text"]:focus,
        input[type="password"]:focus,
        input[type="text"].password-input:focus {
            outline: none;
            border-color: var(--sun);
            box-shadow: 0 0 0 4px var(--ring);
        }

        .password-wrap {
            position: relative;
        }

        .password-wrap .password-input {
            padding-right: 44px;
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            border: 0;
            background: transparent;
            color: #8b8b8b;
            cursor: pointer;
            width: 28px;
            height: 28px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .toggle-password:hover {
            color: #4f4f4f;
        }

        .toggle-password svg {
            width: 18px;
            height: 18px;
        }

        .hidden {
            display: none;
        }

        .row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 12px;
            font-size: 13px;
            color: #666;
        }

        .row label {
            margin: 0;
            font-weight: 600;
            letter-spacing: 0;
        }

        .btn {
            width: 100%;
            margin-top: 18px;
            background: linear-gradient(180deg, var(--sun), var(--sun-deep));
            color: #fff;
            border: 0;
            padding: 12px 14px;
            border-radius: 12px;
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
            margin-top: 12px;
            font-size: 12px;
            color: #888;
            text-align: center;
        }

        @media (max-width: 840px) {
            .card {
                grid-template-columns: 1fr;
            }

            .brand {
                padding-bottom: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="wrap">
        <div class="card">
            <section class="brand">
                <span class="badge">TOKO INTAN</span>
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
                    <input id="username" name="username" type="text" value="{{ old('username') }}" required>

                    <label for="password">PASSWORD</label>
                    <div class="password-wrap">
                        <input id="password" class="password-input" name="password" type="password" required>
                        <button type="button" id="togglePassword" class="toggle-password" aria-label="Tampilkan password">
                            {{-- eye (dibuka) --}}
                            <svg id="iconEye" class="hidden" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.477 0 8.268 2.943 9.542 7-1.274 4.057-5.065 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>

                            {{-- eye-off (awal/tertutup) --}}
                            <svg id="iconEyeOff" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.27-2.943-9.543-7a9.965 9.965 0 012.43-3.95M9.88 9.88A3 3 0 0114.12 14.12M6.1 6.1A9.955 9.955 0 0112 5c4.478 0 8.27 2.943 9.543 7a9.97 9.97 0 01-4.186 5.02M3 3l18 18" />
                            </svg>
                        </button>
                    </div>

                    <div class="row">
                        <label>
                            <input type="checkbox" name="remember" value="1"> Remember me
                        </label>
                    </div>

                    <button class="btn" type="submit">Login</button>
                </form>

                <div class="foot">TOKO INTAN • Sistem POS</div>
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