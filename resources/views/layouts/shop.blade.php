<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Vanora Parfum · Decant Parfum Original')</title>
    <meta name="description" content="@yield('description', 'Parfum original dalam kemasan decant 2 ml, 5 ml, dan 10 ml. Pesan online, ambil di toko atau diantar.')">
    <link rel="icon" type="image/png" href="{{ asset('images/vanora-icon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="d-flex flex-column min-vh-100">
<nav class="navbar navbar-expand-lg shop-nav sticky-top">
    <div class="container">
        <a class="navbar-brand" href="{{ route('home') }}">
            <img src="{{ asset('images/vanora-logo.png') }}" alt="Vanora Parfum">
        </a>

        <div class="d-flex align-items-center gap-1 order-lg-3">
            <a href="{{ route('cart.show') }}" class="cart-link" aria-label="Keranjang, {{ $cartCount }} barang">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M6 7h12l-1 13H7L6 7z"/><path d="M9 7a3 3 0 0 1 6 0"/>
                </svg>
                @if ($cartCount > 0)
                    <span class="cart-badge">{{ $cartCount }}</span>
                @endif
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#shopNav"
                    aria-controls="shopNav" aria-expanded="false" aria-label="Buka menu">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>

        <div class="collapse navbar-collapse order-lg-2" id="shopNav">
            <ul class="navbar-nav mx-lg-auto">
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">Beranda</a></li>
                <li class="nav-item"><a class="nav-link {{ request()->routeIs('catalog.*') ? 'active' : '' }}" href="{{ route('catalog.index') }}">Katalog</a></li>
                <li class="nav-item">
                    @if (Route::has('orders.track'))
                        <a class="nav-link {{ request()->routeIs('orders.*') ? 'active' : '' }}" href="{{ route('orders.track') }}">Cek Pesanan</a>
                    @else
                        <span class="nav-link disabled">Cek Pesanan</span>
                    @endif
                </li>
            </ul>
        </div>
    </div>
</nav>

<main class="flex-grow-1">
    @php $showGlobalErrors = $errors->any() && ! $__env->hasSection('inline_errors'); @endphp

    @if (session('status') || session('error') || $showGlobalErrors)
        <div class="container pt-3">
            @if (session('status'))
                <div class="alert alert-success d-flex flex-wrap justify-content-between align-items-center gap-2 mb-0" role="alert">
                    <span>{{ session('status') }}</span>
                    @if (session('cart_link'))
                        <a href="{{ route('cart.show') }}" class="alert-link">Lihat keranjang</a>
                    @endif
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger mb-0" role="alert">{{ session('error') }}</div>
            @endif
            @if ($showGlobalErrors)
                <div class="alert alert-danger mb-0" role="alert">
                    @foreach ($errors->all() as $message)
                        <div>{{ $message }}</div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    @yield('content')
</main>

<footer class="shop-footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-5">
                <h2 class="font-display">{{ $store['store_name'] ?? 'Vanora Parfum' }}</h2>
                <p class="mb-0">Parfum original dalam kemasan decant, supaya Anda bisa mencoba banyak aroma tanpa membeli botol penuh.</p>
            </div>
            <div class="col-sm-6 col-lg-4">
                <h2 class="font-display">Kontak</h2>
                @if (! empty($store['store_address']))
                    <p class="mb-2">{{ $store['store_address'] }}</p>
                @endif
                @if (! empty($store['store_whatsapp']))
                    <a href="https://wa.me/{{ $store['store_whatsapp'] }}" target="_blank" rel="noopener" class="btn btn-wa btn-sm">
                        WhatsApp {{ \App\Support\Phone::display($store['store_whatsapp']) }}
                    </a>
                @endif
            </div>
            <div class="col-sm-6 col-lg-3">
                <h2 class="font-display">Jam Buka</h2>
                <p class="mb-2">{{ $store['opening_hours'] ?? '-' }}</p>
                @if (! empty($store['delivery_area']))
                    <p class="small mb-0">Pengantaran tersedia untuk wilayah {{ $store['delivery_area'] }}.</p>
                @endif
            </div>
        </div>
        <hr class="border-secondary my-4">
        <p class="small mb-0">&copy; {{ now()->year }} {{ $store['store_name'] ?? 'Vanora Parfum' }}</p>
    </div>
</footer>
</body>
</html>
