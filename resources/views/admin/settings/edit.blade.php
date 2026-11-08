@extends('layouts.admin')

@section('title', 'Pengaturan')

@section('content')
    <div class="mb-4">
        <h1 class="h2 mb-1">Pengaturan Toko</h1>
        <p class="text-secondary mb-0">Informasi kontak, ongkir, dan petunjuk pembayaran.</p>
    </div>

    <form method="POST" action="{{ route('admin.settings.update') }}" style="max-width: 760px">
        @csrf
        @method('PUT')

        <div class="panel mb-3">
            <h2 class="h4 mb-3">Informasi Toko</h2>

            <div class="mb-3">
                <label for="store_name" class="form-label">Nama toko</label>
                <input type="text" id="store_name" name="store_name" value="{{ old('store_name', $values['store_name']) }}"
                       class="form-control @error('store_name') is-invalid @enderror" maxlength="100" required>
                @error('store_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="mb-3">
                <label for="store_address" class="form-label">Alamat toko (untuk pickup)</label>
                <input type="text" id="store_address" name="store_address" value="{{ old('store_address', $values['store_address']) }}"
                       class="form-control @error('store_address') is-invalid @enderror" maxlength="255" required>
                @error('store_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="store_whatsapp" class="form-label">Nomor WhatsApp toko</label>
                    <input type="text" id="store_whatsapp" name="store_whatsapp" inputmode="tel"
                           value="{{ old('store_whatsapp', $values['store_whatsapp']) }}"
                           class="form-control @error('store_whatsapp') is-invalid @enderror" maxlength="30" required>
                    @error('store_whatsapp') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    <div class="form-text">Boleh 0812… atau +62812…; disimpan sebagai 62812….</div>
                </div>
                <div class="col-md-6">
                    <label for="opening_hours" class="form-label">Jam buka</label>
                    <input type="text" id="opening_hours" name="opening_hours" value="{{ old('opening_hours', $values['opening_hours']) }}"
                           class="form-control @error('opening_hours') is-invalid @enderror" maxlength="100" required>
                    @error('opening_hours') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        <div class="panel mb-3">
            <h2 class="h4 mb-3">Pengiriman</h2>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="delivery_area" class="form-label">Wilayah pengantaran</label>
                    <input type="text" id="delivery_area" name="delivery_area" value="{{ old('delivery_area', $values['delivery_area']) }}"
                           class="form-control @error('delivery_area') is-invalid @enderror" maxlength="100" required>
                    @error('delivery_area') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6">
                    <label for="shipping_fee" class="form-label">Biaya pengiriman (Rp)</label>
                    <input type="number" id="shipping_fee" name="shipping_fee" min="0" max="1000000" step="1"
                           value="{{ old('shipping_fee', $values['shipping_fee']) }}"
                           class="form-control @error('shipping_fee') is-invalid @enderror" required>
                    @error('shipping_fee') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        <div class="panel mb-4">
            <h2 class="h4 mb-3">Pembayaran</h2>
            <div class="mb-3">
                <label for="payment_info_cash" class="form-label">Petunjuk pembayaran tunai</label>
                <textarea id="payment_info_cash" name="payment_info_cash" rows="2" maxlength="500"
                          class="form-control @error('payment_info_cash') is-invalid @enderror" required>{{ old('payment_info_cash', $values['payment_info_cash']) }}</textarea>
                @error('payment_info_cash') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="mb-3">
                <label for="payment_info_qris" class="form-label">Petunjuk pembayaran QRIS</label>
                <textarea id="payment_info_qris" name="payment_info_qris" rows="2" maxlength="500"
                          class="form-control @error('payment_info_qris') is-invalid @enderror" required>{{ old('payment_info_qris', $values['payment_info_qris']) }}</textarea>
                @error('payment_info_qris') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div>
                <label for="qris_image_path" class="form-label">Gambar QRIS (path atau URL)</label>
                <input type="text" id="qris_image_path" name="qris_image_path"
                       value="{{ old('qris_image_path', $values['qris_image_path']) }}"
                       class="form-control @error('qris_image_path') is-invalid @enderror" maxlength="500" placeholder="images/qris.png">
                @error('qris_image_path') <div class="invalid-feedback">{{ $message }}</div> @enderror
                @if ($values['qris_image_path'])
                    <img src="{{ str_starts_with($values['qris_image_path'], 'http') ? $values['qris_image_path'] : asset($values['qris_image_path']) }}"
                         alt="QRIS toko" class="mt-3 rounded border" style="max-width: 200px; height: auto;">
                @endif
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg">Simpan Pengaturan</button>
    </form>
@endsection
