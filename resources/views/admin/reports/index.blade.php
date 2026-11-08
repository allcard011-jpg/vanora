@extends('layouts.admin')

@section('title', 'Laporan Penjualan')

@section('content')
    @php $isMonth = $filters['group'] === 'month'; @endphp

    <div class="mb-4">
        <h1 class="h2 mb-1">Laporan Penjualan</h1>
        <p class="text-secondary mb-0">Dihitung dari pesanan berstatus Selesai, berdasarkan tanggal pesanan diselesaikan (WITA).</p>
    </div>

    @if ($filterErrors->any())
        <div class="alert alert-warning" role="alert">
            @foreach ($filterErrors->all() as $message)
                <div>{{ $message }}</div>
            @endforeach
            <div class="small mt-1">Menampilkan periode bawaan.</div>
        </div>
    @endif

    <div class="d-flex flex-wrap gap-2 mb-3">
        @foreach ($presets as [$label, $query])
            <a href="{{ route('admin.reports.index', $query) }}" class="btn btn-sm btn-outline-secondary">{{ $label }}</a>
        @endforeach
    </div>

    <form method="GET" action="{{ route('admin.reports.index') }}" class="panel mb-4">
        <div class="row g-2 align-items-end">
            <div class="col-6 col-md-3">
                <label for="from" class="form-label small mb-1">Tanggal mulai</label>
                <input type="date" id="from" name="from" value="{{ $filters['from'] }}" class="form-control" required>
            </div>
            <div class="col-6 col-md-3">
                <label for="to" class="form-label small mb-1">Tanggal akhir</label>
                <input type="date" id="to" name="to" value="{{ $filters['to'] }}" class="form-control" required>
            </div>
            <div class="col-6 col-md-3">
                <label for="group" class="form-label small mb-1">Tampilkan per</label>
                <select id="group" name="group" class="form-select">
                    <option value="day" @selected(! $isMonth)>Hari</option>
                    <option value="month" @selected($isMonth)>Bulan</option>
                </select>
            </div>
            <div class="col-6 col-md-3 d-grid">
                <button type="submit" class="btn btn-primary">Terapkan</button>
            </div>
        </div>
    </form>

    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-label">Total Transaksi</div>
                <div class="stat-value">{{ number_format($report['totals']['orders'], 0, ',', '.') }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-label">Produk Terjual (unit)</div>
                <div class="stat-value">{{ number_format($report['totals']['units'], 0, ',', '.') }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card stat-card-accent">
                <div class="stat-label">Total Omzet</div>
                <div class="stat-value">{{ \App\Support\Rupiah::format($report['totals']['revenue']) }}</div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-label">Rata-rata per Transaksi</div>
                <div class="stat-value">{{ \App\Support\Rupiah::format($report['totals']['average']) }}</div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-xl-8">
            <div class="panel">
                <h2 class="h4 mb-3">Rekap {{ $isMonth ? 'Bulanan' : 'Harian' }}</h2>

                @if (empty($report['rows']))
                    <p class="text-secondary mb-0">Belum ada pesanan selesai pada periode ini.</p>
                @else
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>{{ $isMonth ? 'Bulan' : 'Tanggal' }}</th>
                                    <th class="text-center">Transaksi</th>
                                    <th class="text-center">Unit</th>
                                    <th class="text-end">Produk</th>
                                    <th class="text-end">Ongkir</th>
                                    <th class="text-end">Omzet</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($report['rows'] as $row)
                                    <tr>
                                        <td class="text-nowrap">{{ $row['label'] }}</td>
                                        <td class="text-center">{{ $row['orders'] }}</td>
                                        <td class="text-center">{{ $row['units'] }}</td>
                                        <td class="text-end text-nowrap">{{ \App\Support\Rupiah::format($row['subtotal']) }}</td>
                                        <td class="text-end text-nowrap">{{ \App\Support\Rupiah::format($row['shipping']) }}</td>
                                        <td class="text-end text-nowrap fw-semibold">{{ \App\Support\Rupiah::format($row['revenue']) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="fw-semibold">
                                    <td>Total</td>
                                    <td class="text-center">{{ $report['totals']['orders'] }}</td>
                                    <td class="text-center">{{ $report['totals']['units'] }}</td>
                                    <td class="text-end text-nowrap">{{ \App\Support\Rupiah::format($report['totals']['subtotal']) }}</td>
                                    <td class="text-end text-nowrap">{{ \App\Support\Rupiah::format($report['totals']['shipping']) }}</td>
                                    <td class="text-end text-nowrap">{{ \App\Support\Rupiah::format($report['totals']['revenue']) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <div class="col-xl-4">
            <div class="panel">
                <h2 class="h4 mb-3">Produk Terlaris</h2>

                @if (empty($report['top']))
                    <p class="text-secondary mb-0">Belum ada data.</p>
                @else
                    <ol class="list-unstyled mb-0">
                        @foreach ($report['top'] as $i => $item)
                            <li class="d-flex justify-content-between gap-3 mb-3">
                                <div>
                                    <div class="fw-medium">{{ $i + 1 }}. {{ $item['product_name'] }}</div>
                                    <div class="small text-secondary">{{ $item['brand_name'] }}</div>
                                </div>
                                <div class="text-end text-nowrap">
                                    <div class="fw-semibold">{{ $item['units'] }} unit</div>
                                    <div class="small text-secondary">{{ \App\Support\Rupiah::format($item['revenue']) }}</div>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>
        </div>
    </div>
@endsection
