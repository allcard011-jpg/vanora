@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    <div class="mb-4">
        <h1 class="h2 mb-1">Dashboard</h1>
        <p class="text-secondary mb-0">Ringkasan toko Vanora.</p>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-label">Jumlah Produk</div>
                <div class="stat-value">{{ number_format($stats['products'], 0, ',', '.') }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-label">Jumlah Pesanan</div>
                <div class="stat-value">{{ number_format($stats['orders'], 0, ',', '.') }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-label">Pesanan Pending</div>
                <div class="stat-value">{{ number_format($stats['pending'], 0, ',', '.') }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-label">Pesanan Selesai</div>
                <div class="stat-value">{{ number_format($stats['completed'], 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <div class="stat-card stat-card-accent mb-4">
        <div class="stat-label">Total Penjualan (pesanan selesai)</div>
        <div class="stat-value">{{ \App\Support\Rupiah::format($stats['revenue']) }}</div>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="panel h-100">
                <h2 class="h4 mb-3">Penjualan 7 Hari Terakhir</h2>
                @foreach ($week as $day)
                    <div class="mb-3">
                        <div class="d-flex justify-content-between small">
                            <span>{{ $day['label'] }}</span>
                            <span class="fw-semibold">{{ \App\Support\Rupiah::format($day['revenue']) }}
                                <span class="text-secondary fw-normal">· {{ $day['orders'] }} pesanan</span>
                            </span>
                        </div>
                        <div class="bar-track mt-1">
                        <div class="bar" @style(['width: '.(int) round($day['revenue'] / $maxRevenue * 100).'%'])></div>
                        </div>
                    </div>
                @endforeach
                <p class="small text-secondary mb-0">Omzet dihitung pada tanggal pesanan diselesaikan (WITA).</p>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="panel h-100">
                <h2 class="h4 mb-3">Pesanan Terbaru</h2>
                @if ($recentOrders->isEmpty())
                    <p class="text-secondary mb-0">Belum ada pesanan.</p>
                @else
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>No. Pesanan</th>
                                    <th>Pelanggan</th>
                                    <th>Jenis</th>
                                    <th class="text-end">Total</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recentOrders as $order)
                                    <tr>
                                        <td class="text-nowrap">{{ $order->order_number }}</td>
                                        <td>{{ $order->customer_name }}</td>
                                        <td>{{ $order->fulfillment_type->label() }}</td>
                                        <td class="text-end text-nowrap">{{ \App\Support\Rupiah::format($order->total) }}</td>
                                        <td><span class="{{ $order->status->badgeClass() }}">{{ $order->status->label() }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
