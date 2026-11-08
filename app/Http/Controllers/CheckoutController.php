<?php

namespace App\Http\Controllers;

use App\Enums\FulfillmentType;
use App\Enums\PaymentMethod;
use App\Models\Setting;
use App\Rules\IndonesianPhone;
use App\Services\CartService;
use App\Services\OrderService;
use App\Support\OrderAccess;
use App\Support\Phone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(private CartService $cart, private OrderService $orders) {}

    public function show(): View|RedirectResponse
    {
        $cart = $this->cart->summary();

        if ($cart['lines']->isEmpty()) {
            return redirect()->route('cart.show')->with('error', 'Keranjang Anda masih kosong.');
        }

        $settings = Setting::whereIn('key', [
            'shipping_fee', 'delivery_area', 'store_address', 'opening_hours',
            'payment_info_cash', 'payment_info_qris',
        ])->pluck('value', 'key');

        return view('shop.checkout', [
            'cart' => $cart,
            'settings' => $settings,
            'shippingFee' => (int) ($settings['shipping_fee'] ?? 0),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'min:2', 'max:100'],
            'whatsapp' => ['required', 'string', 'max:30', new IndonesianPhone],
            'fulfillment_type' => ['required', Rule::enum(FulfillmentType::class)],
            'payment_method' => ['required', Rule::enum(PaymentMethod::class)],
            'delivery_address' => ['required_if:fulfillment_type,delivery', 'nullable', 'string', 'min:10', 'max:500'],
            'confirm_area' => ['accepted_if:fulfillment_type,delivery'],
            'customer_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $order = $this->orders->place([
            'customer_name' => preg_replace('/\s+/', ' ', $data['customer_name']),
            'whatsapp' => Phone::normalize($data['whatsapp']),
            'fulfillment_type' => FulfillmentType::from($data['fulfillment_type']),
            'payment_method' => PaymentMethod::from($data['payment_method']),
            'delivery_address' => $data['delivery_address'] ?? null,
            'customer_notes' => $data['customer_notes'] ?? null,
        ]);

        $this->cart->clear();
        OrderAccess::grant($order->order_number);

        return redirect()->route('orders.show', $order->order_number)->with('placed', true);
    }
}
