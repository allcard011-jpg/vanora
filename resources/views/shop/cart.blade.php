@extends('layouts.shop')

@section('title', 'Keranjang · Vanora')

@section('content')
    <div class="container py-4 py-md-5">
        <h1 class="section-title mb-3">Keranjang</h1>

        @foreach ($cart['notices'] as $notice)
            <div class="alert alert-warning" role="alert">{{ $notice }}</div>
        @endforeach

        @if ($cart['lines']->isEmpty())
            <div class="text-center py-5">
                <p class="text-secondary mb-3">Keranjang Anda masih kosong.</p>
                <a href="{{ route('catalog.index') }}" class="btn btn-primary btn-lg">Mulai Belanja</a>
            </div>
        @else
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="panel py-1">
                        @foreach ($cart['lines'] as $line)
                            <div class="cart-line">
                                <a href="{{ route('catalog.show', $line['product_slug']) }}" class="cart-thumb">
                                    @if ($line['image_url'])
                                        <img src="{{ $line['image_url'] }}" alt="" loading="lazy">
                                    @else
                                        <div class="product-placeholder" aria-hidden="true"><span>{{ mb_substr($line['product_name'], 0, 1) }}</span></div>
                                    @endif
                                </a>

                                <div class="flex-grow-1">
                                    <div class="product-brand">{{ $line['brand_name'] }}</div>
                                    <a href="{{ route('catalog.show', $line['product_slug']) }}" class="product-name text-decoration-none">{{ $line['product_name'] }}</a>
                                    <div class="small text-secondary">{{ $line['size_ml'] }} ml &middot; {{ \App\Support\Rupiah::format($line['unit_price']) }} / buah</div>

                                    <div class="d-flex flex-wrap align-items-center gap-3 mt-2">
                                        <form method="POST" action="{{ route('cart.update', $line['variant_id']) }}">
                                            @csrf
                                            @method('PATCH')
                                            <div class="qty" data-qty>
                                                <button type="button" data-qty-step="-1" aria-label="Kurangi jumlah">&minus;</button>
                                                <input type="number" name="quantity" value="{{ $line['quantity'] }}" min="1" max="{{ $line['max'] }}"
                                                       step="1" inputmode="numeric" data-autosubmit aria-label="Jumlah {{ $line['product_name'] }}">
                                                <button type="button" data-qty-step="1" aria-label="Tambah jumlah">+</button>
                                            </div>
                                        </form>

                                        <form method="POST" action="{{ route('cart.destroy', $line['variant_id']) }}"
                                              data-confirm="Hapus {{ $line['product_name'] }} {{ $line['size_ml'] }} ml dari keranjang?">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-link btn-sm text-danger p-0">Hapus</button>
                                        </form>
                                    </div>
                                </div>

                                <div class="cart-price text-end fw-semibold text-nowrap">{{ \App\Support\Rupiah::format($line['line_total']) }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="panel">
                        <h2 class="h4 mb-3">Ringkasan</h2>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-secondary">Subtotal ({{ $cart['count'] }} barang)</span>
                            <span>{{ \App\Support\Rupiah::format($cart['subtotal']) }}</span>
                        </div>
                        <div class="d-flex justify-content-between mb-3 small text-secondary">
                            <span>Ongkir</span>
                            <span>Dihitung saat checkout</span>
                        </div>
                        <div class="d-flex justify-content-between fs-5 fw-semibold border-top pt-3 mb-3">
                            <span>Total</span>
                            <span>{{ \App\Support\Rupiah::format($cart['subtotal']) }}</span>
                        </div>

                        @if (Route::has('checkout.show'))
                            <a href="{{ route('checkout.show') }}" class="btn btn-aurora btn-lg w-100">Lanjut ke Checkout</a>
                        @else
                            <button type="button" class="btn btn-secondary btn-lg w-100" disabled>Checkout segera hadir</button>
                        @endif
                        <a href="{{ route('catalog.index') }}" class="btn btn-link w-100 mt-2">Lanjut belanja</a>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
