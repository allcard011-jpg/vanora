@extends('layouts.admin')

@section('title', 'Produk')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h2 mb-1">Produk</h1>
            <p class="text-secondary mb-0">Kelola parfum, harga, dan stok.</p>
        </div>
        <a href="{{ route('admin.products.create') }}" class="btn btn-primary">Tambah Produk</a>
    </div>

    <form method="GET" class="panel mb-3">
        <div class="row g-2">
            <div class="col-12 col-md-4">
                <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari nama atau merek…">
            </div>
            <div class="col-6 col-md-2">
                <select name="category" class="form-select" aria-label="Kategori">
                    <option value="">Semua kategori</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(request('category') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="brand" class="form-select" aria-label="Merek">
                    <option value="">Semua merek</option>
                    @foreach ($brands as $brand)
                        <option value="{{ $brand->id }}" @selected(request('brand') == $brand->id)>{{ $brand->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6 col-md-2">
                <select name="status" class="form-select" aria-label="Status">
                    <option value="">Semua status</option>
                    <option value="active" @selected(request('status') === 'active')>Aktif</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>Nonaktif</option>
                </select>
            </div>
            <div class="col-6 col-md-2 d-grid">
                <button type="submit" class="btn btn-outline-secondary">Terapkan</button>
            </div>
        </div>
    </form>

    <div class="panel">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Kategori</th>
                        <th>Harga</th>
                        <th class="text-center">Total stok</th>
                        <th>Status</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($products as $product)
                        <tr>
                            <td>
                                <div class="fw-medium">{{ $product->name }} @if ($product->is_featured)<span title="Produk unggulan">★</span>@endif</div>
                                <div class="small text-secondary">{{ $product->brand->name }}</div>
                            </td>
                            <td>{{ $product->category->name }}</td>
                            <td class="text-nowrap">
                                @if ($product->variants_min_price === null)
                                    <span class="text-secondary">Belum ada ukuran</span>
                                @elseif ($product->variants_min_price === $product->variants_max_price)
                                    {{ \App\Support\Rupiah::format((int) $product->variants_min_price) }}
                                @else
                                    {{ \App\Support\Rupiah::format((int) $product->variants_min_price) }} –
                                    {{ \App\Support\Rupiah::format((int) $product->variants_max_price) }}
                                @endif
                            </td>
                            <td class="text-center">{{ (int) $product->variants_sum_stock }}</td>
                            <td>
                                <span class="{{ $product->is_active ? 'badge-on' : 'badge-off' }}">{{ $product->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-sm btn-outline-secondary">Ubah</a>
                                <form method="POST" action="{{ route('admin.products.destroy', $product) }}" class="d-inline"
                                      data-confirm="Hapus produk {{ $product->name }} beserta semua ukurannya? Riwayat pesanan tetap tersimpan.">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-secondary">Tidak ada produk yang cocok.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3">{{ $products->links('pagination::bootstrap-5') }}</div>
    </div>
@endsection
