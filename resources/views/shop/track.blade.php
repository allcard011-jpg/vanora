@extends('layouts.shop')

@section('title', 'Cek Pesanan · Vanora')
@section('inline_errors', '1')

@section('content')
    <div class="container py-4 py-md-5" style="max-width: 560px">
        <h1 class="section-title mb-2">Cek Pesanan</h1>
        <p class="text-secondary">Masukkan nomor pesanan dan nomor WhatsApp yang Anda pakai saat memesan.</p>

        <form method="POST" action="{{ route('orders.lookup') }}" class="panel" novalidate>
            @csrf

            <div class="mb-3">
                <label for="order_number" class="form-label">Nomor pesanan</label>
                <input type="text" id="order_number" name="order_number" value="{{ old('order_number') }}"
                       class="form-control form-control-lg @error('order_number') is-invalid @enderror"
                       placeholder="VNR-20261107-0001" maxlength="30" autocomplete="off" required>
                @error('order_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="mb-4">
                <label for="whatsapp" class="form-label">Nomor WhatsApp</label>
                <input type="tel" id="whatsapp" name="whatsapp" value="{{ old('whatsapp') }}" inputmode="tel"
                       class="form-control form-control-lg @error('whatsapp') is-invalid @enderror"
                       placeholder="081234567890" maxlength="30" required>
                @error('whatsapp') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <button type="submit" class="btn btn-primary btn-lg w-100">Cek Status</button>
        </form>
    </div>
@endsection
