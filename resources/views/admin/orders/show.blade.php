@extends('layouts.admin')

@section('title', 'Pesanan '.$order->order_number)

@section('content')
    @php
        $flow = \App\Enums\OrderStatus::flow($order->fulfillment_type);
        $index = array_search($order->status, $flow, true);
        $cancelled = $order->status === \App\Enums\OrderStatus::Cancelled;
        $isDelivery = $order->fulfillment_type === \App\Enums\FulfillmentType::Delivery;
        $waText = "Halo {$order->customer_name}, terkait pesanan {$order->order_number} di Vanora Parfum.";
    @endphp

    <div class="mb-4">
        <a href="{{ route('admin.orders.index') }}" class="small">&larr; Daftar pesanan</a>
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-1">
            <h1 class="h2 mb-0">{{ $order->order_number }}</h1>
            <span class="{{ $order->status->badgeClass() }}">{{ $order->status->label() }}</span>
        </div>
        <div class="small text-secondary">Dipesan {{ $order->created_at->translatedFormat('d F Y, H:i') }} WITA</div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            @foreach ($errors->all() as $message)
                <div>{{ $message }}</div>
            @endforeach
        </div>
    @endif

    <div class="row g-3">
        <div class="col-xl-7">
            <div class="panel mb-3">
                <h2 class="h4 mb-3">Pelanggan</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-secondary fw-normal">Nama</dt>
                    <dd class="col-sm-8">{{ $order->customer_name }}</dd>

                    <dt class="col-sm-4 text-secondary fw-normal">WhatsApp</dt>
                    <dd class="col-sm-8">
                        {{ \App\Support\Phone::display($order->whatsapp) }}
                        <a href="https://wa.me/{{ $order->whatsapp }}?text={{ rawurlencode($waText) }}" target="_blank" rel="noopener"
                           class="btn btn-wa btn-sm ms-2">Chat</a>
                    </dd>

                    @if ($order->customer_notes)
                        <dt class="col-sm-4 text-secondary fw-normal">Catatan pelanggan</dt>
                        <dd class="col-sm-8">{!! nl2br(e($order->customer_notes)) !!}</dd>
                    @endif
                </dl>
            </div>

            <div class="panel mb-3">
                <h2 class="h4 mb-3">Pengambilan &amp; Pembayaran</h2>
                <dl class="row mb-0">
                    <dt class="col-sm-4 text-secondary fw-normal">Jenis</dt>
                    <dd class="col-sm-8">{{ $order->fulfillment_type->label() }}</dd>

                    @if ($isDelivery)
                        <dt class="col-sm-4 text-secondary fw-normal">Alamat pengantaran</dt>
                        <dd class="col-sm-8">{!! nl2br(e($order->delivery_address)) !!}</dd>
                    @endif

                    <dt class="col-sm-4 text-secondary fw-normal">Metode bayar</dt>
                    <dd class="col-sm-8 mb-0">
                        {{ $order->payment_method->label() }}
                        <div class="small text-secondary">Dibayar manual saat pesanan diambil atau diterima.</div>
                    </dd>
                </dl>

                @if ($isDelivery)
                    <form method="POST" action="{{ route('admin.orders.delivery-notes', $order) }}" class="mt-3 border-top pt-3">
                        @csrf
                        @method('PATCH')
                        <label for="delivery_notes" class="form-label">Catatan pengantaran</label>
                        <textarea id="delivery_notes" name="delivery_notes" rows="2" maxlength="500"
                                  class="form-control @error('delivery_notes') is-invalid @enderror"
                                  placeholder="Contoh: diantar oleh Andi, estimasi 20.00">{{ old('delivery_notes', $order->delivery_notes) }}</textarea>
                        @error('delivery_notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <button type="submit" class="btn btn-outline-secondary btn-sm mt-2">Simpan catatan</button>
                    </form>
                @endif
            </div>

            <div class="panel">
                <h2 class="h4 mb-3">Rincian Pesanan</h2>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead>
                            <tr><th>Produk</th><th class="text-end">Harga</th><th class="text-center">Jumlah</th><th class="text-end">Subtotal</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($order->items as $item)
                                <tr>
                                    <td>
                                        <div class="fw-medium">{{ $item->product_name }}</div>
                                        <div class="small text-secondary">{{ $item->brand_name }} &middot; {{ $item->size_ml }} ml</div>
                                    </td>
                                    <td class="text-end text-nowrap">{{ \App\Support\Rupiah::format($item->unit_price) }}</td>
                                    <td class="text-center">{{ $item->quantity }}</td>
                                    <td class="text-end text-nowrap">{{ \App\Support\Rupiah::format($item->line_total) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

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
            </div>
        </div>

        <div class="col-xl-5">
            <div class="panel mb-3">
                <h2 class="h4 mb-3">Status</h2>

                @if ($cancelled)
                    <div class="alert alert-danger mb-0" role="alert">
                        <strong>Pesanan dibatalkan.</strong>
                        @if ($order->cancel_reason)
                            <div>Alasan: {{ $order->cancel_reason }}</div>
                        @endif
                    </div>
                @else
                    <ol class="tracker mb-3">
                        @foreach ($flow as $i => $step)
                            <li class="{{ $i < $index ? 'done' : ($i === $index ? 'current' : '') }}">{{ $step->label() }}</li>
                        @endforeach
                    </ol>
                @endif

                @if ($next)
                    <form method="POST" action="{{ route('admin.orders.advance', $order) }}">
                        @csrf
                        <input type="hidden" name="current_status" value="{{ $order->status->value }}">
                        <label for="note" class="form-label small">Catatan untuk riwayat (opsional)</label>
                        <textarea id="note" name="note" rows="2" maxlength="500" class="form-control mb-2"></textarea>
                        <button type="submit" class="btn btn-aurora w-100">Ubah ke: {{ $next->label() }}</button>
                    </form>
                @elseif (! $cancelled)
                    <div class="alert alert-success mb-0" role="alert">Pesanan selesai.</div>
                @endif

                @if (! $order->status->isFinal())
                    <details class="mt-3 border-top pt-3" @if ($errors->has('reason')) open @endif>
                        <summary class="text-danger fw-medium">Batalkan pesanan</summary>
                        <form method="POST" action="{{ route('admin.orders.cancel', $order) }}" class="mt-3"
                              data-confirm="Batalkan pesanan {{ $order->order_number }}? Stok akan dikembalikan dan tindakan ini tidak bisa diurungkan.">
                            @csrf
                            <input type="hidden" name="current_status" value="{{ $order->status->value }}">
                            <label for="reason" class="form-label small">Alasan pembatalan (wajib)</label>
                            <textarea id="reason" name="reason" rows="2" maxlength="500" required
                                      class="form-control mb-2 @error('reason') is-invalid @enderror">{{ old('reason') }}</textarea>
                            @error('reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <button type="submit" class="btn btn-danger w-100">Batalkan Pesanan</button>
                        </form>
                    </details>
                @endif
            </div>

            <div class="panel">
                <h2 class="h4 mb-3">Riwayat</h2>
                <ul class="list-unstyled mb-0">
                    @foreach ($order->histories->reverse() as $history)
                        <li class="mb-3">
                            <span class="{{ $history->status->badgeClass() }}">{{ $history->status->label() }}</span>
                            <span class="small text-secondary ms-1">{{ $history->created_at->translatedFormat('d M Y, H:i') }}</span>
                            <div class="small text-secondary">oleh {{ $history->user?->email ?? 'pelanggan / sistem' }}</div>
                            @if ($history->note)
                                <div class="small">{{ $history->note }}</div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endsection
