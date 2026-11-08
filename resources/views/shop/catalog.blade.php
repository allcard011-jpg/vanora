@extends('layouts.shop')

@section('title', 'Katalog Parfum · Vanora')

@section('content')
    <div class="container py-4 py-md-5">
        <h1 class="section-title mb-3">Katalog</h1>

        <form method="GET" action="{{ route('catalog.index') }}" class="panel mb-4">
            <div class="row g-2">
                <div class="col-12 col-lg-4">
                    <input type="search" name="q" value="{{ $filters['q'] }}" class="form-control" placeholder="Cari parfum atau merek…" aria-label="Cari">
                </div>
                <div class="col-6 col-lg-2">
                    <select name="kategori" class="form-select" aria-label="Kategori">
                        <option value="">Semua kategori</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->slug }}" @selected($filters['kategori'] === $category->slug)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <select name="merek" class="form-select" aria-label="Merek">
                        <option value="">Semua merek</option>
                        @foreach ($brands as $brand)
                            <option value="{{ $brand->slug }}" @selected($filters['merek'] === $brand->slug)>{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-lg-2">
                    <select name="urut" class="form-select" aria-label="Urutkan">
                        @foreach ($sorts as $value => $label)
                            <option value="{{ $value }}" @selected($filters['urut'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-6 col-lg-2 d-grid">
                    <button type="submit" class="btn btn-primary">Terapkan</button>
                </div>
            </div>
            @if ($filters['q'] !== '' || $filters['kategori'] !== '' || $filters['merek'] !== '')
                <div class="mt-2"><a href="{{ route('catalog.index') }}" class="small">Hapus semua filter</a></div>
            @endif
        </form>

        @if ($products->isEmpty())
            <div class="text-center py-5">
                <p class="text-secondary mb-3">Tidak ada parfum yang cocok dengan pencarian Anda.</p>
                <a href="{{ route('catalog.index') }}" class="btn btn-primary">Lihat Semua Parfum</a>
            </div>
        @else
            <p class="text-secondary small mb-3">{{ $products->total() }} parfum</p>
            <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-3">
                @foreach ($products as $product)
                    <div class="col">@include('shop._product-card', ['product' => $product])</div>
                @endforeach
            </div>
            <div class="mt-4">{{ $products->links('pagination::bootstrap-5') }}</div>
        @endif
    </div>
@endsection
