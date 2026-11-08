@extends('layouts.admin')

@section('title', 'Kategori')

@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h1 class="h2 mb-1">Kategori</h1>
            <p class="text-secondary mb-0">Pengelompokan parfum berdasarkan target pemakai.</p>
        </div>
        <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">Tambah Kategori</a>
    </div>

    <div class="panel">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr><th>Nama</th><th class="text-center">Produk</th><th>Status</th><th class="text-end">Aksi</th></tr>
                </thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr>
                            <td class="fw-medium">{{ $category->name }}</td>
                            <td class="text-center">{{ $category->products_count }}</td>
                            <td>
                                <span class="{{ $category->is_active ? 'badge-on' : 'badge-off' }}">{{ $category->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                            </td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('admin.categories.edit', $category) }}" class="btn btn-sm btn-outline-secondary">Ubah</a>
                                <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" class="d-inline"
                                      data-confirm="Hapus kategori {{ $category->name }}?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-secondary">Belum ada kategori.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
