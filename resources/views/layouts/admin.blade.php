<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Dashboard') · Admin Vanora</title>
    <link rel="icon" type="image/png" href="{{ asset('images/vanora-icon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
@php
    $nav = [
        ['Dashboard', 'admin.dashboard'],
        ['Pesanan', 'admin.orders.index'],
        ['Produk', 'admin.products.index'],
        ['Merek', 'admin.brands.index'],
        ['Kategori', 'admin.categories.index'],
        ['Laporan', 'admin.reports.index'],
        ['Pengaturan', 'admin.settings.edit'],
    ];
@endphp
<div class="admin-shell">
    <aside class="admin-sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="adminSidebar" aria-label="Menu admin">
        <div class="offcanvas-header d-lg-none">
            <span class="fw-semibold">Menu</span>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#adminSidebar" aria-label="Tutup"></button>
        </div>
        <div class="offcanvas-body d-flex flex-column p-3 p-lg-4">
            <a href="{{ route('admin.dashboard') }}" class="d-none d-lg-block mb-4">
                <img src="{{ asset('images/vanora-logo.png') }}" alt="Vanora Parfum" class="admin-logo">
            </a>

            <ul class="nav nav-pills admin-nav flex-column gap-1 mb-auto">
                @foreach ($nav as [$label, $routeName])
                    @if (Route::has($routeName))
                        @php
                            $pattern = $routeName === 'admin.dashboard'
                                ? $routeName
                                : \Illuminate\Support\Str::beforeLast($routeName, '.').'.*';
                        @endphp
                        <li class="nav-item">
                            <a class="nav-link {{ request()->routeIs($pattern) ? 'active' : '' }}" href="{{ route($routeName) }}">{{ $label }}</a>
                        </li>
                    @else
                        <li class="nav-item">
                            <span class="nav-link disabled">{{ $label }} <small>(segera)</small></span>
                        </li>
                    @endif
                @endforeach
            </ul>

            <div class="border-top pt-3 mt-4">
                <div class="small text-secondary text-truncate mb-2">{{ auth()->user()->email }}</div>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary btn-sm w-100">Keluar</button>
                </form>
            </div>
        </div>
    </aside>

    <div class="admin-main">
        <header class="d-lg-none d-flex align-items-center justify-content-between bg-white border-bottom px-3 py-2 sticky-top">
            <a href="{{ route('admin.dashboard') }}">
                <img src="{{ asset('images/vanora-logo.png') }}" alt="Vanora Parfum" style="height: 40px; width: auto;">
            </a>
            <button class="btn btn-outline-secondary btn-sm" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebar" aria-controls="adminSidebar">
                Menu
            </button>
        </header>

        <main class="p-3 p-md-4 p-xl-5">
            @if (session('status'))
                <div class="alert alert-success" role="alert">{{ session('status') }}</div>
            @endif
                        @if (session('error'))
                <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
            @endif

            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
