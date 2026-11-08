@extends('layouts.admin')

@section('title', 'Merek')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h2 mb-1">Merek</h1>
            <p class="text-secondary mb-0">Merek parfum yang dijual.</p>
        </div>
        <a href="{{ route('admin.brands.create') }}" class="btn btn-primary">Tambah Merek</a>
    </div>

    <div class="panel">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr><th>Nama</th><th class="text-center">Produk</th><th class="text-end">Aksi</th></tr>
                </thead>
                <tbody>
                    @forelse ($brands as $brand)
                        <tr>
                            <td class="fw-medium">{{ $brand->name }}</td>
                            <td class="text-center">{{ $brand->products_count }}</td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('admin.brands.edit', $brand) }}" class="btn btn-sm btn-outline-secondary">Ubah</a>
                                <form method="POST" action="{{ route('admin.brands.destroy', $brand) }}" class="d-inline"
                                      data-confirm="Hapus merek {{ $brand->name }}?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-secondary">Belum ada merek.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
