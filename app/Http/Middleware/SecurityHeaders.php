<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /** Halaman berisi data pribadi atau sesi: jangan disimpan cache browser atau proxy. */
    private const PRIVATE_PATHS = ['admin', 'admin/*', 'checkout', 'keranjang', 'pesanan/*', 'cek-pesanan'];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $headers = $response->headers;

        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'DENY');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        // CSP dilewati saat mode debug (halaman error pengembangan memakai skrip inline)
        // dan saat server pengembangan Vite aktif (file public/hot ada).
        if (! config('app.debug') && ! is_file(public_path('hot'))) {
            $headers->set('Content-Security-Policy', $this->contentSecurityPolicy());
        }

        if ($request->isSecure() && app()->isProduction()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        if ($request->is(...self::PRIVATE_PATHS)) {
            $headers->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }

    private function contentSecurityPolicy(): string
    {
        return implode('; ', [
            "default-src 'self'",
            "script-src 'self'",
            // 'unsafe-inline' hanya untuk gaya: beberapa elemen memakai atribut style="…".
            "style-src 'self' 'unsafe-inline'",
            // https: karena foto produk boleh berupa URL https eksternal; data: untuk ikon Bootstrap.
            "img-src 'self' data: https:",
            "font-src 'self'",
            "connect-src 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
        ]);
    }
}
