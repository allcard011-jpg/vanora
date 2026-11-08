@extends('layouts.shop')

@section('title', $product->name.' · '.$product->brand->name.' · Vanora')
@section('description', $product->description ? \Illuminate\Support\Str::limit($product->description, 150) : 'Decant '.$product->brand->name.' '.$product->name.' di Vanora Parfum.')

@section('content')
    @php
        $defaultVariant = $variants->first(fn ($v) => $v->stock > 0);
        $defaultMax = $defaultVariant ? min($defaultVariant->stock, \App\Services\CartService::MAX_PER_LINE) : 1;
    @endphp

    <div class="container py-4 py-md-5">
        <a href="{{ route('catalog.index') }}" class="small">&larr; Katalog</a>

        <div class="row g-4 g-lg-5 mt-1">
            <div class="col-lg-5">
                <div class="product-hero-media">
                    @if ($product->image_url)
                        <img src="{{ $product->image_url }}" alt="{{ $product->brand->name }} {{ $product->name }}">
                    @else
                        <div class="product-placeholder" aria-hidden="true"><span>{{ mb_substr($product->name, 0, 1) }}</span></div>
                    @endif
                </div>
            </div>

            <div class="col-lg-7">
                <div class="product-brand">{{ $product->brand->name }} &middot; {{ $product->category->name }}</div>
                <h1 class="display-5 mb-3">{{ $product->name }}</h1>

                @if ($product->description)
                    <p class="text-secondary">{{ $product->description }}</p>
                @endif

                @if ($product->top_notes || $product->middle_notes || $product->base_notes)
                    <dl class="notes-list panel mb-4">
                        @if ($product->top_notes)
                            <dt>Top notes</dt><dd>{{ $product->top_notes }}</dd>
                        @endif
                        @if ($product->middle_notes)
                            <dt>Middle notes</dt><dd>{{ $product->middle_notes }}</dd>
                        @endif
                        @if ($product->base_notes)
                            <dt>Base notes</dt><dd class="mb-0">{{ $product->base_notes }}</dd>
                        @endif
                    </dl>
                @endif

                <form method="POST" action="{{ route('cart.store') }}" id="buyForm">
                    @csrf

                    <h2 class="h5 mb-2">Pilih ukuran</h2>
                    <div class="d-flex flex-wrap gap-2 mb-4">
                        @foreach ($variants as $variant)
                            @php $soldOut = $variant->stock < 1; @endphp
                            <input type="radio" class="btn-check" name="variant_id" id="variant-{{ $variant->id }}"
                                   value="{{ $variant->id }}" data-stock="{{ $variant->stock }}"
                                   @checked($defaultVariant && $variant->id === $defaultVariant->id) @disabled($soldOut)>
                            <label class="size-pill" for="variant-{{ $variant->id }}">
                                <span class="size-ml">{{ $variant->size_ml }} ml</span>
                                <span class="size-price">{{ \App\Support\Rupiah::format($variant->price) }}</span>
                                @if ($soldOut)
                                    <span class="size-note">Habis</span>
                                @elseif ($variant->stock <= 3)
                                    <span class="size-note">Sisa {{ $variant->stock }}</span>
                                @endif
                            </label>
                        @endforeach
                    </div>

                    <div class="buy-bar">
                        <div class="qty" data-qty>
                            <button type="button" data-qty-step="-1" aria-label="Kurangi jumlah">&minus;</button>
                            <input type="number" name="quantity" value="1" min="1" max="{{ $defaultMax }}" step="1" inputmode="numeric" aria-label="Jumlah">
                            <button type="button" data-qty-step="1" aria-label="Tambah jumlah">+</button>
                        </div>
                        <button type="submit" class="btn btn-aurora btn-lg flex-grow-1">Tambah ke Keranjang</button>
                    </div>
                </form>
            </div>
        </div>

        @if ($related->isNotEmpty())
            <section class="mt-5 pt-4">
                <h2 class="section-title mb-3">Parfum Serupa</h2>
                <div class="row row-cols-2 row-cols-md-4 g-3">
                    @foreach ($related as $item)
                        <div class="col">@include('shop._product-card', ['product' => $item])</div>
                    @endforeach
                </div>
            </section>
        @endif
    </div>
@endsection
