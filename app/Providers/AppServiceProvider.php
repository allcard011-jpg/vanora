<?php

namespace App\Providers;

use App\Models\Setting;
use App\Services\CartService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Sabuk pengaman: semua URL yang dibuat aplikasi memakai https di production.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Batas pembuatan pesanan per alamat IP.
        RateLimiter::for('checkout', fn (Request $request) => [
            Limit::perMinute(5)->by($request->ip()),
            Limit::perHour(20)->by($request->ip()),
        ]);

        // Data yang dibutuhkan setiap halaman toko: info toko (footer) dan jumlah isi keranjang.
        View::composer('layouts.shop', function ($view) {
            $keys = ['store_name', 'store_address', 'store_whatsapp', 'opening_hours', 'delivery_area'];

            $view->with([
                'store' => Setting::whereIn('key', $keys)->pluck('value', 'key'),
                'cartCount' => app(CartService::class)->count(),
            ]);
        });
    }
}
