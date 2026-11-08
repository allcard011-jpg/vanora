<?php

namespace App\Http\Controllers;

use App\Services\CartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(private CartService $cart) {}

    public function show(): View
    {
        return view('shop.cart', ['cart' => $this->cart->summary()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'between:1,'.CartService::MAX_PER_LINE],
        ]);

        $this->cart->add($data['variant_id'], $data['quantity']);

        return back()->with('status', 'Ditambahkan ke keranjang.')->with('cart_link', true);
    }

    public function update(Request $request, int $variantId): RedirectResponse
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'between:0,'.CartService::MAX_PER_LINE],
        ]);

        $this->cart->update($variantId, $data['quantity']);

        return redirect()->route('cart.show');
    }

    public function destroy(int $variantId): RedirectResponse
    {
        $this->cart->remove($variantId);

        return redirect()->route('cart.show')->with('status', 'Produk dihapus dari keranjang.');
    }
}
