@extends('layouts.shop')

@section('content')
    <section class="hero">
        <div class="container">
            <div class="row">
                <div class="col-lg-7">
                    <p class="hero-eyebrow">Parfum original &middot; Decant 2 &middot; 5 &middot; 10 ml</p>
                    <h1>Aroma Original, Ukuran Secukupnya</h1>
                    <p class="lead mb-4">Coba berbagai aroma favorit tanpa harus membeli botol penuh. Pesan online, lalu ambil di toko atau minta diantar.</p>
                    <a href="{{ route('catalog.index') }}" class="btn btn-aurora btn-lg px-4">Lihat Katalog</a>
                </div>
            </div>
        </div>
    </section>

    <section class="container py-5">
        <div class="row justify-content-center text-center">
            <div class="col-lg-8">
                <h2 class="section-title">Tentang Vanora</h2>
                <p class="text-secondary mb-0">Vanora menjual parfum original dalam kemasan decant: parfum dipindahkan ke botol kecil berukuran 2 ml, 5 ml, atau 10 ml. Harga lebih terjangkau, cocok untuk mencoba aroma baru atau dibawa bepergian.</p>
            </div>
        </div>
    </section>

    @if ($categories->isNotEmpty())
        <section class="container pb-5">
            <h2 class="section-title mb-3">Kategori</h2>
            <div class="row g-3">
                @foreach ($categories as $category)
                    <div class="col-12 col-sm-4">
                        <a href="{{ route('catalog.index', ['kategori' => $category->slug]) }}" class="category-card">
                            <div class="font-display">{{ $category->name }}</div>
                            <div class="small text-secondary">{{ $category->available_count }} parfum</div>
                        </a>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if ($featured->isNotEmpty())
        <section class="container pb-5">
            <div class="d-flex justify-content-between align-items-end mb-3">
                <h2 class="section-title mb-0">Produk Unggulan</h2>
                <a href="{{ route('catalog.index') }}" class="small">Lihat semua</a>
            </div>
            <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3">
                @foreach ($featured as $product)
                    <div class="col">@include('shop._product-card', ['product' => $product])</div>
                @endforeach
            </div>
        </section>
    @endif

    <section class="container pb-4">
        <div class="d-flex justify-content-between align-items-end mb-3">
            <h2 class="section-title mb-0">Produk Terbaru</h2>
            <a href="{{ route('catalog.index', ['urut' => 'terbaru']) }}" class="small">Lihat semua</a>
        </div>
        @if ($newest->isEmpty())
            <p class="text-secondary">Produk akan segera tersedia.</p>
        @else
            <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3">
                @foreach ($newest as $product)
                    <div class="col">@include('shop._product-card', ['product' => $product])</div>
                @endforeach
            </div>
        @endif

        <div class="text-center mt-5">
            <a href="{{ route('catalog.index') }}" class="btn btn-primary btn-lg px-4">Jelajahi Katalog</a>
        </div>
    </section>
@endsection
