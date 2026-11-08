<?php

use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\OrderTrackingController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductVariantController;
use App\Http\Controllers\Admin\SettingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/katalog', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/produk/{slug}', [CatalogController::class, 'show'])->name('catalog.show');

Route::get('/keranjang', [CartController::class, 'show'])->name('cart.show');
Route::middleware('throttle:60,1')->group(function () {
    Route::post('/keranjang', [CartController::class, 'store'])->name('cart.store');
    Route::patch('/keranjang/{variantId}', [CartController::class, 'update'])->whereNumber('variantId')->name('cart.update');
    Route::delete('/keranjang/{variantId}', [CartController::class, 'destroy'])->whereNumber('variantId')->name('cart.destroy');
});

Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');
Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:checkout')->name('checkout.store');

Route::get('/cek-pesanan', [OrderTrackingController::class, 'form'])->name('orders.track');
Route::post('/cek-pesanan', [OrderTrackingController::class, 'lookup'])->middleware('throttle:20,1')->name('orders.lookup');
Route::get('/pesanan/{orderNumber}', [OrderTrackingController::class, 'show'])
    ->where('orderNumber', 'VNR-[0-9]{8}-[0-9]{4,}')
    ->name('orders.show');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('login', [LoginController::class, 'create'])->name('login');
        Route::post('login', [LoginController::class, 'store'])->name('login.store');
    });

    Route::middleware('auth')->group(function () {
        Route::post('logout', [LoginController::class, 'destroy'])->name('logout');
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('orders/{order}', [AdminOrderController::class, 'show'])->whereNumber('order')->name('orders.show');
        Route::post('orders/{order}/advance', [AdminOrderController::class, 'advance'])->whereNumber('order')->name('orders.advance');
        Route::post('orders/{order}/cancel', [AdminOrderController::class, 'cancel'])->whereNumber('order')->name('orders.cancel');
        Route::patch('orders/{order}/delivery-notes', [AdminOrderController::class, 'deliveryNotes'])->whereNumber('order')->name('orders.delivery-notes');

        Route::resource('categories', CategoryController::class)->except('show');
        Route::resource('brands', BrandController::class)->except('show');
        Route::resource('products', ProductController::class)->except('show');

        Route::post('products/{product}/variants', [ProductVariantController::class, 'store'])->name('products.variants.store');
        Route::put('variants/{variant}', [ProductVariantController::class, 'update'])->name('variants.update');
        Route::delete('variants/{variant}', [ProductVariantController::class, 'destroy'])->name('variants.destroy');

        Route::get('settings', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
    });
});
