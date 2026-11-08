<?php

namespace App\Services;

use App\Models\ProductVariant;
use Illuminate\Validation\ValidationException;

class CartService
{
    private const SESSION_KEY = 'cart';

    public const MAX_PER_LINE = 20;

    /** @return array<int, int> variant_id => jumlah */
    public function raw(): array
    {
        $cart = session(self::SESSION_KEY, []);
        $clean = [];

        if (is_array($cart)) {
            foreach ($cart as $id => $quantity) {
                if ((int) $id > 0 && (int) $quantity > 0) {
                    $clean[(int) $id] = (int) $quantity;
                }
            }
        }

        return $clean;
    }

    public function count(): int
    {
        return array_sum($this->raw());
    }

    public function add(int $variantId, int $quantity): void
    {
        $variant = $this->purchasableVariant($variantId);
        $cart = $this->raw();
        $newQuantity = ($cart[$variantId] ?? 0) + $quantity;

        $this->assertWithinLimit($variant, $newQuantity);

        $cart[$variantId] = $newQuantity;
        session([self::SESSION_KEY => $cart]);
    }

    public function update(int $variantId, int $quantity): void
    {
        $cart = $this->raw();

        if (! isset($cart[$variantId])) {
            return;
        }

        if ($quantity <= 0) {
            $this->remove($variantId);

            return;
        }

        $variant = $this->purchasableVariant($variantId);
        $this->assertWithinLimit($variant, $quantity);

        $cart[$variantId] = $quantity;
        session([self::SESSION_KEY => $cart]);
    }

    public function remove(int $variantId): void
    {
        $cart = $this->raw();
        unset($cart[$variantId]);
        session([self::SESSION_KEY => $cart]);
    }

    public function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public function isPurchasable(ProductVariant $variant): bool
    {
        $product = $variant->product;

        return $variant->is_active
            && $variant->stock > 0
            && $product !== null
            && $product->is_active
            && $product->category !== null
            && $product->category->is_active;
    }

    /**
     * Isi keranjang dari data database TERBARU. Baris yang tidak valid dibuang,
     * jumlah yang melebihi stok dikoreksi, dan pemberitahuannya dikembalikan.
     *
     * @return array{lines: \Illuminate\Support\Collection, subtotal: int, count: int, notices: array<int, string>}
     */
    public function summary(): array
    {
        $cart = $this->raw();
        $lines = collect();
        $notices = [];
        $clean = [];

        if ($cart !== []) {
            $variants = ProductVariant::with(['product.brand', 'product.category'])
                ->whereIn('id', array_keys($cart))
                ->get()
                ->keyBy('id');

            foreach ($cart as $id => $quantity) {
                $variant = $variants->get($id);

                if (! $variant || ! $this->isPurchasable($variant)) {
                    $name = $variant ? "{$variant->product->name} {$variant->size_ml} ml" : 'Sebuah produk';
                    $notices[] = "{$name} dihapus dari keranjang karena sudah tidak tersedia.";

                    continue;
                }

                $max = $this->maxFor($variant);

                if ($quantity > $max) {
                    $notices[] = "Jumlah {$variant->product->name} {$variant->size_ml} ml disesuaikan menjadi {$max} sesuai stok.";
                    $quantity = $max;
                }

                $clean[$id] = $quantity;
                $lines->push([
                    'variant_id' => $variant->id,
                    'product_name' => $variant->product->name,
                    'brand_name' => $variant->product->brand->name,
                    'product_slug' => $variant->product->slug,
                    'image_url' => $variant->product->image_url,
                    'size_ml' => $variant->size_ml,
                    'unit_price' => $variant->price,
                    'quantity' => $quantity,
                    'line_total' => $variant->price * $quantity,
                    'max' => $max,
                ]);
            }

            if ($clean !== $cart) {
                session([self::SESSION_KEY => $clean]);
            }
        }

        return [
            'lines' => $lines,
            'subtotal' => (int) $lines->sum('line_total'),
            'count' => (int) $lines->sum('quantity'),
            'notices' => $notices,
        ];
    }

    private function maxFor(ProductVariant $variant): int
    {
        return min($variant->stock, self::MAX_PER_LINE);
    }

    private function purchasableVariant(int $variantId): ProductVariant
    {
        $variant = ProductVariant::with('product.category')->find($variantId);

        if (! $variant || ! $this->isPurchasable($variant)) {
            throw ValidationException::withMessages([
                'quantity' => 'Ukuran ini sedang tidak tersedia.',
            ]);
        }

        return $variant;
    }

    private function assertWithinLimit(ProductVariant $variant, int $quantity): void
    {
        if ($quantity > $this->maxFor($variant)) {
            $message = $variant->stock < self::MAX_PER_LINE
                ? "Stok {$variant->size_ml} ml tinggal {$variant->stock}."
                : 'Maksimal '.self::MAX_PER_LINE." per ukuran dalam satu pesanan.";

            throw ValidationException::withMessages(['quantity' => $message]);
        }
    }
}
