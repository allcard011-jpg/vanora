<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title') · Vanora Parfum</title>
    <link rel="icon" type="image/png" href="{{ asset('images/vanora-icon.png') }}">
    <style>
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 1.5rem; background: #faf9f6; color: #12141c; font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; }
        main { max-width: 440px; text-align: center; }
        img { width: 180px; height: auto; }
        .code { margin: 1.5rem 0 0.25rem; font-size: 3.5rem; font-weight: 700; line-height: 1; color: #7c3aed; }
        h1 { margin: 0.25rem 0 0.75rem; font-size: 1.5rem; color: #1f1259; }
        p { color: #5b6275; line-height: 1.6; margin: 0; }
        a.btn { display: inline-block; margin-top: 1.25rem; padding: 0.75rem 1.5rem; border-radius: 0.6rem; background: #1f1259; color: #fff; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
<main>
    <img src="{{ asset('images/vanora-logo.png') }}" alt="Vanora Parfum">
    <div class="code">@yield('code')</div>
    <h1>@yield('heading')</h1>
    <p>@yield('message')</p>
    <a class="btn" href="{{ url('/') }}">Kembali ke Beranda</a>
</main>
</body>
</html>
