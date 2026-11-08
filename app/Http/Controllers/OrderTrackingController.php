<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Setting;
use App\Support\OrderAccess;
use App\Support\Phone;
use App\Support\WhatsApp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrderTrackingController extends Controller
{
    private const MAX_FAILED_LOOKUPS = 10;
    private const LOCK_SECONDS = 600;

    public function form(): View
    {
        return view('shop.track');
    }

    public function lookup(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'order_number' => ['required', 'string', 'max:30'],
            'whatsapp' => ['required', 'string', 'max:30'],
        ]);

        $key = 'order-lookup:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_FAILED_LOOKUPS)) {
            throw ValidationException::withMessages([
                'order_number' => 'Terlalu banyak percobaan. Coba lagi dalam '.RateLimiter::availableIn($key).' detik.',
            ]);
        }

        $phone = Phone::normalize($data['whatsapp']);

        $order = $phone
            ? Order::where('order_number', Str::upper(trim($data['order_number'])))->where('whatsapp', $phone)->first()
            : null;

        if (! $order) {
            RateLimiter::hit($key, self::LOCK_SECONDS);

            throw ValidationException::withMessages([
                'order_number' => 'Pesanan tidak ditemukan. Periksa nomor pesanan dan nomor WhatsApp Anda.',
            ]);
        }

        // Sengaja TIDAK mereset hitungan saat berhasil, agar tidak bisa dipakai menghapus batas.
        OrderAccess::grant($order->order_number);

        return redirect()->route('orders.show', $order->order_number);
    }

    public function show(string $orderNumber): View|RedirectResponse
    {
        if (! OrderAccess::allows($orderNumber)) {
            return redirect()->route('orders.track')
                ->with('error', 'Masukkan nomor pesanan dan nomor WhatsApp untuk melihat pesanan.');
        }

        $order = Order::with(['items', 'histories'])->where('order_number', $orderNumber)->firstOrFail();

        $settings = Setting::whereIn('key', [
            'store_whatsapp', 'store_address', 'opening_hours',
            'payment_info_cash', 'payment_info_qris', 'qris_image_path',
        ])->pluck('value', 'key');

        $whatsappUrl = ! empty($settings['store_whatsapp'])
            ? WhatsApp::orderUrl($order, $settings['store_whatsapp'])
            : null;

        return view('shop.order', compact('order', 'settings', 'whatsappUrl'));
    }
}
