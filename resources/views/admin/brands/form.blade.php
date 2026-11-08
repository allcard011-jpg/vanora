@extends('layouts.admin')

@section('title', $brand->exists ? 'Ubah Merek' : 'Tambah Merek')

@section('content')
    <div class="mb-4">
        <a href="{{ route('admin.brands.index') }}" class="small">&larr; Daftar merek</a>
        <h1 class="h2 mt-1 mb-0">{{ $brand->exists ? 'Ubah Merek' : 'Tambah Merek' }}</h1>
    </div>

    <form method="POST" class="panel" style="max-width: 520px"
          action="{{ $brand->exists ? route('admin.brands.update', $brand) : route('admin.brands.store') }}">
        @csrf
        @if ($brand->exists)
            @method('PUT')
        @endif

        <div class="mb-4">
            <label for="name" class="form-label">Nama merek</label>
            <input type="text" id="name" name="name" value="{{ old('name', $brand->name) }}"
                   class="form-control @error('name') is-invalid @enderror" maxlength="80" required>
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <button type="submit" class="btn btn-primary">Simpan</button>
    </form>
@endsection
