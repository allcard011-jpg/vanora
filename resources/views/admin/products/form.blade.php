@extends('layouts.admin')

@section('title', $product->exists ? 'Ubah Produk' : 'Tambah Produk')

@section('content')
    @php $editing = $product->exists; @endphp

    <div class="mb-4">
        <a href="{{ route('admin.products.index') }}" class="small">&larr; Daftar produk</a>
        <h1 class="h2 mt-1 mb-0">{{ $editing ? $product->name : 'Tambah Produk' }}</h1>
    </div>

    <div class="row g-4">
        <div class="col-xl-6">
            <form method="POST" class="panel"
                  action="{{ $editing ? route('admin.products.update', $product) : route('admin.products.store') }}">
                @csrf
                @if ($editing)
                    @method('PUT')
                @endif

                <div class="mb-3">
                    <label for="name" class="form-label">Nama parfum</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $product->name) }}"
                           class="form-control @error('name') is-invalid @enderror" maxlength="120" required>
                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                        <label for="brand_id" class="form-label">Merek</label>
                        <select id="brand_id" name="brand_id" class="form-select @error('brand_id') is-invalid @enderror" required>
                            <option value="">Pilih merek</option>
                            @foreach ($brands as $brand)
                                <option value="{{ $brand->id }}" @selected(old('brand_id', $product->brand_id) == $brand->id)>{{ $brand->name }}</option>
                            @endforeach
                        </select>
                        @error('brand_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-sm-6">
                        <label for="category_id" class="form-label">Kategori</label>
                        <select id="category_id" name="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                            <option value="">Pilih kategori</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('category_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mb-3">
                    <label for="description" class="form-label">Deskripsi</label>
                    <textarea id="description" name="description" rows="3" maxlength="2000"
                              class="form-control @error('description') is-invalid @enderror">{{ old('description', $product->description) }}</textarea>
                    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label for="top_notes" class="form-label">Top notes</label>
                    <input type="text" id="top_notes" name="top_notes" value="{{ old('top_notes', $product->top_notes) }}"
                           class="form-control @error('top_notes') is-invalid @enderror" maxlength="255">
                    @error('top_notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label for="middle_notes" class="form-label">Middle notes</label>
                    <input type="text" id="middle_notes" name="middle_notes" value="{{ old('middle_notes', $product->middle_notes) }}"
                           class="form-control @error('middle_notes') is-invalid @enderror" maxlength="255">
                    @error('middle_notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label for="base_notes" class="form-label">Base notes</label>
                    <input type="text" id="base_notes" name="base_notes" value="{{ old('base_notes', $product->base_notes) }}"
                           class="form-control @error('base_notes') is-invalid @enderror" maxlength="255">
                    @error('base_notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label for="image_path" class="form-label">Foto produk (path atau URL)</label>
                    <div class="d-flex gap-3 align-items-start">
                        @if ($product->image_url)
                            <img src="{{ $product->image_url }}" alt="Foto {{ $product->name }}" class="thumb">
                        @endif
                        <div class="flex-grow-1">
                            <input type="text" id="image_path" name="image_path" value="{{ old('image_path', $product->image_path) }}"
                                   class="form-control @error('image_path') is-invalid @enderror" maxlength="500"
                                   placeholder="images/products/vanilla-clouds.jpg">
                            @error('image_path') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">Taruh file di <code>public/images/products/</code>, lalu isi path-nya. Boleh juga URL <code>https://…</code>. Kosongkan untuk tampilan placeholder.</div>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-4 mb-4">
                    <div class="form-check">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" id="is_active" name="is_active" value="1" class="form-check-input"
                               @checked(old('is_active', $product->is_active))>
                        <label for="is_active" class="form-check-label">Aktif (tampil di katalog)</label>
                    </div>
                    <div class="form-check">
                        <input type="hidden" name="is_featured" value="0">
                        <input type="checkbox" id="is_featured" name="is_featured" value="1" class="form-check-input"
                               @checked(old('is_featured', $product->is_featured))>
                        <label for="is_featured" class="form-check-label">Produk unggulan</label>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">{{ $editing ? 'Simpan Perubahan' : 'Buat Produk' }}</button>
            </form>
        </div>

        @if ($editing)
            <div class="col-xl-6">
                <div class="panel">
                    <h2 class="h4 mb-1">Ukuran, Harga, dan Stok</h2>
                    <p class="small text-secondary mb-3">Produk baru tampil di katalog setelah punya minimal satu ukuran aktif dengan stok.</p>

                    @forelse ($variants as $variant)
                        @php
                            $bag = 'variant'.$variant->id;
                            $isOld = (string) old('_variant') === (string) $variant->id;
                        @endphp
                        <form method="POST" action="{{ route('admin.variants.update', $variant) }}" class="variant-row">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="_variant" value="{{ $variant->id }}">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="variant-size">{{ $variant->size_ml }} ml</span>
                                <span class="{{ $variant->is_active ? 'badge-on' : 'badge-off' }}">{{ $variant->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                            </div>
                            <div class="row g-2 align-items-end">
                                <div class="col-6 col-sm-4">
                                    <label class="form-label small mb-1" for="price-{{ $variant->id }}">Harga (Rp)</label>
                                    <input type="number" id="price-{{ $variant->id }}" name="price" min="1" max="10000000" step="1"
                                           value="{{ $isOld ? old('price') : $variant->price }}"
                                           class="form-control @error('price', $bag) is-invalid @enderror" required>
                                    @error('price', $bag) <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-6 col-sm-3">
                                    <label class="form-label small mb-1" for="stock-{{ $variant->id }}">Stok</label>
                                    <input type="number" id="stock-{{ $variant->id }}" name="stock" min="0" max="100000" step="1"
                                           value="{{ $isOld ? old('stock') : $variant->stock }}"
                                           class="form-control @error('stock', $bag) is-invalid @enderror" required>
                                    @error('stock', $bag) <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-12 col-sm-5 d-flex align-items-center justify-content-between gap-2">
                                    <div class="form-check mb-0">
                                        <input type="hidden" name="is_active" value="0">
                                        <input type="checkbox" id="active-{{ $variant->id }}" name="is_active" value="1" class="form-check-input"
                                               @checked($isOld ? old('is_active') : $variant->is_active)>
                                        <label for="active-{{ $variant->id }}" class="form-check-label small">Aktif</label>
                                    </div>
                                    <div class="text-nowrap">
                                        <button type="submit" class="btn btn-sm btn-primary">Simpan</button>
                                        <button type="submit" form="delete-variant-{{ $variant->id }}" class="btn btn-sm btn-outline-danger">Hapus</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                        <form id="delete-variant-{{ $variant->id }}" method="POST" action="{{ route('admin.variants.destroy', $variant) }}"
                              data-confirm="Hapus ukuran {{ $variant->size_ml }} ml? Riwayat pesanan tetap tersimpan.">
                            @csrf
                            @method('DELETE')
                        </form>
                    @empty
                        <p class="text-secondary">Belum ada ukuran. Tambahkan di bawah.</p>
                    @endforelse

                    <hr>
                    <h3 class="h5 mb-3">Tambah Ukuran</h3>
                    @php $isNew = old('_variant') === 'new'; @endphp
                    <form method="POST" action="{{ route('admin.products.variants.store', $product) }}">
                        @csrf
                        <input type="hidden" name="_variant" value="new">
                        <div class="row g-2 align-items-end">
                            <div class="col-4">
                                <label class="form-label small mb-1" for="new-size">Ukuran (ml)</label>
                                <input type="number" id="new-size" name="size_ml" min="1" max="1000" step="1"
                                       value="{{ $isNew ? old('size_ml') : '' }}"
                                       class="form-control @error('size_ml', 'newVariant') is-invalid @enderror" required>
                                @error('size_ml', 'newVariant') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-4">
                                <label class="form-label small mb-1" for="new-price">Harga (Rp)</label>
                                <input type="number" id="new-price" name="price" min="1" max="10000000" step="1"
                                       value="{{ $isNew ? old('price') : '' }}"
                                       class="form-control @error('price', 'newVariant') is-invalid @enderror" required>
                                @error('price', 'newVariant') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-4">
                                <label class="form-label small mb-1" for="new-stock">Stok</label>
                                <input type="number" id="new-stock" name="stock" min="0" max="100000" step="1"
                                       value="{{ $isNew ? old('stock') : '' }}"
                                       class="form-control @error('stock', 'newVariant') is-invalid @enderror" required>
                                @error('stock', 'newVariant') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                        <div class="form-check mt-3 mb-3">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" id="new-active" name="is_active" value="1" class="form-check-input"
                                   @checked($isNew ? old('is_active') : true)>
                            <label for="new-active" class="form-check-label">Aktif</label>
                        </div>
                        <button type="submit" class="btn btn-aurora">Tambah Ukuran</button>
                    </form>
                </div>
            </div>
        @endif
    </div>
@endsection
