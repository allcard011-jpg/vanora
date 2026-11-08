@extends('layouts.shop')

@section('title', 'Checkout · Vanora')
@section('inline_errors', '1')

@section('content')
    @php
        $fulfillment = old('fulfillment_type', 'pickup');
        $payment = old('payment_method', 'cash');
    @endphp

    <div class="container py-4 py-md-5">
        <h1 class="section-title mb-3">Checkout</h1>

        @foreach ($cart['notices'] as $notice)
            <div class="alert alert-warning" role="alert">{{ $notice }}</div>
        @endforeach

        @if ($errors->has('cart'))
            <div class="alert alert-danger" role="alert">
                @foreach ($errors->get('cart') as $message)
                    <div>{{ $message }}</div>
                @endforeach
                <a href="{{ route('cart.show') }}" class="alert-link">Periksa keranjang</a>
            </div>
        @endif

        <form method="POST" action="{{ route('checkout.store') }}" novalidate
              data-checkout data-subtotal="{{ $cart['subtotal'] }}" data-fee="{{ $shippingFee }}">
            @csrf

            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="panel mb-3">
                        <h2 class="h4 mb-3">Data Pemesan</h2>

                        <div class="mb-3">
                            <label for="customer_name" class="form-label">Nama</label>
                            <input type="text" id="customer_name" name="customer_name" value="{{ old('customer_name') }}"
                                   class="form-control form-control-lg @error('customer_name') is-invalid @enderror"
                                   maxlength="100" autocomplete="name" required>
                            @error('customer_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div>
                            <label for="whatsapp" class="form-label">Nomor WhatsApp</label>
                            <input type="tel" id="whatsapp" name="whatsapp" value="{{ old('whatsapp') }}" inputmode="tel"
                                   class="form-control form-control-lg @error('whatsapp') is-invalid @enderror"
                                   maxlength="30" autocomplete="tel" placeholder="081234567890" required>
                            @error('whatsapp') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">Dipakai untuk konfirmasi dan untuk mengecek status pesanan.</div>
                        </div>
                    </div>

                    <div class="panel mb-3">
                        <h2 class="h4 mb-3">Pengambilan Pesanan</h2>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="fulfillment_type" id="ft-pickup" value="pickup" @checked($fulfillment === 'pickup')>
                                <label class="choice" for="ft-pickup">
                                    <div class="choice-title">Ambil di toko</div>
                                    <div class="small text-secondary">Tanpa ongkir</div>
                                </label>
                            </div>
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="fulfillment_type" id="ft-delivery" value="delivery" @checked($fulfillment === 'delivery')>
                                <label class="choice" for="ft-delivery">
                                    <div class="choice-title">Antar ke alamat</div>
                                    <div class="small text-secondary">Ongkir {{ \App\Support\Rupiah::format($shippingFee) }}</div>
                                </label>
                            </div>
                        </div>
                        @error('fulfillment_type') <div class="text-danger small mb-2">{{ $message }}</div> @enderror

                        @if (! empty($settings['store_address']))
                            <p class="small text-secondary mb-0">Alamat toko: {{ $settings['store_address'] }}. Jam buka: {{ $settings['opening_hours'] ?? '-' }}.</p>
                        @endif

                        <div data-delivery-fields class="mt-3" @if ($fulfillment !== 'delivery') hidden @endif>
                            <div class="alert alert-info small">
                                Pengantaran hanya untuk wilayah <strong>{{ $settings['delivery_area'] ?? '-' }}</strong>.
                            </div>

                            <div class="mb-3">
                                <label for="delivery_address" class="form-label">Alamat lengkap</label>
                                <textarea id="delivery_address" name="delivery_address" rows="3" maxlength="500"
                                          class="form-control @error('delivery_address') is-invalid @enderror"
                                          placeholder="Nama jalan, nomor rumah, RT/RW, patokan">{{ old('delivery_address') }}</textarea>
                                @error('delivery_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="form-check">
                                <input type="checkbox" id="confirm_area" name="confirm_area" value="1"
                                       class="form-check-input @error('confirm_area') is-invalid @enderror" @checked(old('confirm_area'))>
                                <label for="confirm_area" class="form-check-label">
                                    Alamat saya berada di wilayah {{ $settings['delivery_area'] ?? '-' }}.
                                </label>
                                @error('confirm_area') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>

                    <div class="panel mb-3">
                        <h2 class="h4 mb-1">Pembayaran</h2>
                        <p class="small text-secondary mb-3">Dibayar langsung saat pesanan diambil atau diterima. Belum ada pembayaran online.</p>

                        <div class="row g-2">
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="payment_method" id="pm-cash" value="cash" @checked($payment === 'cash')>
                                <label class="choice" for="pm-cash">
                                    <div class="choice-title">Tunai</div>
                                </label>
                            </div>
                            <div class="col-6">
                                <input type="radio" class="btn-check" name="payment_method" id="pm-qris" value="qris" @checked($payment === 'qris')>
                                <label class="choice" for="pm-qris">
                                    <div class="choice-title">QRIS</div>
                                </label>
                            </div>
                        </div>
                        @error('payment_method') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
                    </div>

                    <div class="panel">
                        <label for="customer_notes" class="form-label">Catatan (opsional)</label>
                        <textarea id="customer_notes" name="customer_notes" rows="2" maxlength="500"
                                  class="form-control @error('customer_notes') is-invalid @enderror">{{ old('customer_notes') }}</textarea>
                        @error('customer_notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="panel">
                        <h2 class="h4 mb-3">Ringkasan Pesanan</h2>

                        @foreach ($cart['lines'] as $line)
                            <div class="d-flex justify-content-between gap-3 mb-2">
                                <div>
                                    <div class="fw-medium">{{ $line['product_name'] }}</div>
                                    <div class="small text-secondary">{{ $line['size_ml'] }} ml &times; {{ $line['quantity'] }}</div>
                                </div>
                                <div class="text-nowrap">{{ \App\Support\Rupiah::format($line['line_total']) }}</div>
                            </div>
                        @endforeach

                        <div class="border-top pt-3 mt-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-secondary">Subtotal</span>
                                <span>{{ \App\Support\Rupiah::format($cart['subtotal']) }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2" data-fee-row @if ($fulfillment !== 'delivery') hidden @endif>
                                <span class="text-secondary">Ongkir</span>
                                <span>{{ \App\Support\Rupiah::format($shippingFee) }}</span>
                            </div>
                            <div class="d-flex justify-content-between fs-5 fw-semibold border-top pt-3">
                                <span>Total</span>
                                <span data-total>{{ \App\Support\Rupiah::format($cart['subtotal'] + ($fulfillment === 'delivery' ? $shippingFee : 0)) }}</span>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-aurora btn-lg w-100 mt-4">Pesan Sekarang</button>
                        <p class="small text-secondary mt-3 mb-0">Setelah memesan, pesanan Anda menunggu konfirmasi dari toko.</p>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection
