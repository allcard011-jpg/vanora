<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Masuk · Admin Vanora</title>
    <link rel="icon" type="image/png" href="{{ asset('images/vanora-icon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="login-page">
    <div class="login-card">
        <div class="text-center mb-4">
            <img src="{{ asset('images/vanora-logo.png') }}" alt="Vanora Parfum" style="width: 190px; height: auto;">
            <p class="text-secondary small mt-2 mb-0">Masuk ke dashboard admin</p>
        </div>

        <form method="POST" action="{{ route('admin.login.store') }}">
            @csrf

            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}"
                       class="form-control form-control-lg @error('email') is-invalid @enderror"
                       autocomplete="username" required autofocus>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password"
                       class="form-control form-control-lg @error('password') is-invalid @enderror"
                       autocomplete="current-password" required>
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-aurora btn-lg w-100">Masuk</button>
        </form>
    </div>
</div>
</body>
</html>
