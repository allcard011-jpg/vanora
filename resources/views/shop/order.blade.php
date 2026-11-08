@extends('layouts.shop')

@section('title', 'Pesanan '.$order->order_number.' · Vanora')

@section('content')
    @php
        $flow = \App\Enums\OrderStatus::flow($order->fulfillment_type);
        $index = array_search($order->status, $flow, true);
        $cancelled = $order->status === \App\Enums\OrderStatus::Cancelled;
        $isDelivery = $order->fulfillment_type === \App\Enums\FulfillmentType::Delivery;
        $open = ! $order->status->isFinal();
        $qris = $settings['qris_image_path'] ?? null;
    @endphp

    <div class="container py-4 py-md-5" style="max-width: 900px">
        @if (session('placed'))
            <div class="panel text-center mb-4">
                <h1 class="section-title">Pesanan Diterima</h1>
                <p class="mb-0">Terima kasih, {{ $order->customer_name }}! Pesanan Anda sedang menunggu konfirmasi dari toko.</p>
            </div>
        @else
            <h1 class="section-title mb-3">Status Pesanan</h1>
        @endif

        <div class="panel mb-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <div class="small text-secondary">Nomor pesanan</div>
                    <div class="order-number">{{ $order->order_number }}</div>
                </div>
                <span class="{{ $order->status->badgeClass() }}">{{ $order->status->label() }}</span>
            </div>
            <div class="small text-secondary mt-1">Dipesan {{ $order->created_at->translatedFormat('d F Y, H:i') }} WITA</div>

            @if ($whatsappUrl && $open)
                <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" class="btn btn-wa mt-3">Hubungi Toko via WhatsApp</a>
            @endif
        </div>

        <div class="row g-3">
            <div class="col-lg-6">
                <div class="panel h-100">
                    <h2 class="h4 mb-3">Perkembangan</h2>

                    @if ($cancelled)
                        <div class="alert alert-danger mb-0" role="alert">
                            <strong>Pesanan dibatalkan.</strong>
                            @if ($order->cancel_reason)
                                <div>Alasan: {{ $order->cancel_reason }}</div>
                            @endif
                        </div>
                    @else
                        <ol class="tracker mb-0">
                            @foreach ($flow as $i => $step)
                                <li class="{{ $i < $index ? 'done' : ($i === $index ? 'current' : '') }}">{{ $step->label() }}</li>
                            @endforeach
                        </ol>
                    @endif
                </div>
            </div>

            <div class="col-lg-6">
                <div class="panel h-100">
                    <h2 class="h4 mb-3">Pengambilan &amp; Pembayaran</h2>
                    <dl class="mb-0">
                        <dt class="small text-secondary">Jenis</dt>
                        <dd>{{ $order->fulfillment_type->label() }}</dd>

                        @if ($isDelivery)
                            <dt class="small text-secondary">Alamat pengantaran</dt>
                            <dd>{!! nl2br(e($order->delivery_address)) !!}</dd>
                        @elseif (! empty($settings['store_address']))
                            <dt class="small text-secondary">Ambil di</dt>
                            <dd>{{ $settings['store_address'] }}<br><span class="small text-secondary">Jam buka: {{ $settings['opening_hours'] ?? '-' }}</span></dd>
                        @endif

                        <dt class="small text-secondary">Pembayaran</dt>
                        <dd class="mb-0">
                            {{ $order->payment_method->label() }}
                            <div class="small text-secondary">
                                {{ $order->payment_method === \App\Enums\PaymentMethod::Qris ? ($settings['payment_info_qris'] ?? '') : ($settings['payment_info_cash'] ?? '') }}
                            </div>
                        </dd>
                    </dl>

                    @if ($open && $order->payment_method === \App\Enums\PaymentMethod::Qris && $qris)
                        <div class="mt-3 text-center">
                            <img src="{{ str_starts_with($qris, 'http') ? $qris : asset($qris) }}" alt="QRIS toko"
                                 class="img-fluid rounded border" style="max-width: 260px">
                            <div class="small mt-2">Bayar sesuai total: <strong>{{ \App\Support\Rupiah::format($order->total) }}</strong></div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="panel mt-3">
            <h2 class="h4 mb-3">Rincian Pesanan</h2>
            @foreach ($order->items as $item)
                <div class="d-flex justify-content-between gap-3 mb-2">
                    <div>
                        <div class="fw-medium">{{ $item->product_name }}</div>
                        <div class="small text-secondary">{{ $item->brand_name }} &middot; {{ $item->size_ml }} ml &middot; {{ \App\Support\Rupiah::format($item->unit_price) }} &times; {{ $item->quantity }}</div>
                    </div>
                    <div class="text-nowrap">{{ \App\Support\Rupiah::format($item->line_total) }}</div>
                </div>
            @endforeach

            <div class="border-top pt-3 mt-3">
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-secondary">Subtotal</span>
                    <span>{{ \App\Support\Rupiah::format($order->subtotal) }}</span>
                </div>
                @if ($isDelivery)
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-secondary">Ongkir</span>
                        <span>{{ \App\Support\Rupiah::format($order->shipping_fee) }}</span>
                    </div>
                @endif
                <div class="d-flex justify-content-between fs-5 fw-semibold">
                    <span>Total</span>
                    <span>{{ \App\Support\Rupiah::format($order->total) }}</span>
                </div>
            </div>

            @if ($order->customer_notes)
                <div class="small text-secondary mt-3">Catatan Anda: {{ $order->customer_notes }}</div>
            @endif
        </div>

        <div class="panel mt-3">
            <h2 class="h4 mb-3">Riwayat</h2>
            <ul class="list-unstyled mb-0">
                @foreach ($order->histories->reverse() as $history)
                    <li class="mb-2">
                        <span class="{{ $history->status->badgeClass() }}">{{ $history->status->label() }}</span>
                        <span class="small text-secondary ms-1">{{ $history->created_at->translatedFormat('d M Y, H:i') }}</span>
                        @if ($history->note)
                            <div class="small">{{ $history->note }}</div>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="text-center mt-4">
            <a href="{{ route('catalog.index') }}" class="btn btn-primary">Belanja Lagi</a>
        </div>
    </div>
@endsection
