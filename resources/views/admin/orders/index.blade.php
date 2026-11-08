@extends('layouts.admin')

@section('title', 'Pesanan')

@section('content')
    <div class="mb-4">
        <h1 class="h2 mb-1">Pesanan</h1>
        <p class="text-secondary mb-0">Kelola pesanan yang masuk dari pelanggan.</p>
    </div>

    @php
        $total = $counts->sum();
        $pill = fn (?string $s) => array_filter(['status' => $s, 'type' => $filters['type'], 'q' => $filters['q']]);
    @endphp

    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="{{ route('admin.orders.index', $pill(null)) }}"
           class="btn btn-sm {{ $filters['status'] === null ? 'btn-primary' : 'btn-outline-secondary' }}">Semua ({{ $total }})</a>
        @foreach (\App\Enums\OrderStatus::cases() as $case)
            <a href="{{ route('admin.orders.index', $pill($case->value)) }}"
               class="btn btn-sm {{ $filters['status'] === $case->value ? 'btn-primary' : 'btn-outline-secondary' }}">
                {{ $case->label() }} ({{ $counts[$case->value] ?? 0 }})
            </a>
        @endforeach
    </div>

    <form method="GET" class="panel mb-3">
        @if ($filters['status'])
            <input type="hidden" name="status" value="{{ $filters['status'] }}">
        @endif
        <div class="row g-2">
            <div class="col-12 col-md-6">
                <input type="search" name="q" value="{{ $filters['q'] }}" class="form-control"
                       placeholder="Cari nomor pesanan, nama, atau nomor WhatsApp…" aria-label="Cari">
            </div>
            <div class="col-6 col-md-3">
                <select name="type" class="form-select" aria-label="Jenis">
                    <option value="">Semua jenis</option>
                    @foreach (\App\Enums\FulfillmentType::cases() as $case)
                        <option value="{{ $case->value }}" @selected($filters['type'] === $case->value)>{{ $case->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-3 d-grid">
                <button type="submit" class="btn btn-outline-secondary">Terapkan</button>
            </div>
        </div>
    </form>

    <div class="panel">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>No. Pesanan</th>
                        <th>Waktu</th>
                        <th>Pelanggan</th>
                        <th>Jenis</th>
                        <th class="text-end">Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        <tr>
                            <td class="text-nowrap">
                                <a href="{{ route('admin.orders.show', $order) }}" class="fw-medium">{{ $order->order_number }}</a>
                            </td>
                            <td class="text-nowrap small">{{ $order->created_at->translatedFormat('d M Y, H:i') }}</td>
                            <td>
                                <div>{{ $order->customer_name }}</div>
                                <div class="small text-secondary">{{ \App\Support\Phone::display($order->whatsapp) }}</div>
                            </td>
                            <td>{{ $order->fulfillment_type->label() }}</td>
                            <td class="text-end text-nowrap">{{ \App\Support\Rupiah::format($order->total) }}</td>
                            <td><span class="{{ $order->status->badgeClass() }}">{{ $order->status->label() }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-secondary">Tidak ada pesanan yang cocok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">{{ $orders->links('pagination::bootstrap-5') }}</div>
    </div>
@endsection
