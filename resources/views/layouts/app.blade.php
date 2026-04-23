<!-- filepath: resources/views/layouts/app.blade.php -->
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Kasir')</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
</head>

<body class="bg-gray-100">
    <!-- Navbar -->
    <nav class="bg-white shadow px-6 py-4 flex justify-between items-center mb-4">
        <div class="font-bold text-xl">TOKO INTAN</div>
        <div>
            <span class="mr-4">Kasir</span>
            <a href="/logout" class="text-red-600 font-semibold">Logout</a>
        </div>
    </nav>
    <div class="flex">
        <!-- Sidebar -->
        <aside class="w-48 bg-white shadow h-screen p-4">
            <ul>
                <li class="mb-2"><a href="/kasir" class="font-semibold text-orange-600">Kasir</a></li>
                <li class="mb-2"><a href="/kasir/transaksi" class="font-semibold text-orange-600">Transaksi</a></li>
            </ul>
        </aside>
        <!-- Main Content -->
        <main class="flex-1 p-8">
            @yield('content')
        </main>
    </div>
</body>

</html>