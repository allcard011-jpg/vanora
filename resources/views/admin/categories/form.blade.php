@extends('layouts.admin')

@section('title', $category->exists ? 'Ubah Kategori' : 'Tambah Kategori')

@section('content')
    <div class="mb-4">
        <a href="{{ route('admin.categories.index') }}" class="small">&larr; Daftar kategori</a>
        <h1 class="h2 mt-1 mb-0">{{ $category->exists ? 'Ubah Kategori' : 'Tambah Kategori' }}</h1>
    </div>

    <form method="POST" class="panel" style="max-width: 520px"
          action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}">
        @csrf
        @if ($category->exists)
            @method('PUT')
        @endif

        <div class="mb-3">
            <label for="name" class="form-label">Nama kategori</label>
            <input type="text" id="name" name="name" value="{{ old('name', $category->name) }}"
                   class="form-control @error('name') is-invalid @enderror" maxlength="50" required>
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="form-check mb-4">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" id="is_active" name="is_active" value="1" class="form-check-input"
                   @checked(old('is_active', $category->is_active))>
            <label for="is_active" class="form-check-label">Aktif (tampil di katalog)</label>
        </div>

        <button type="submit" class="btn btn-primary">Simpan</button>
    </form>
@endsection
