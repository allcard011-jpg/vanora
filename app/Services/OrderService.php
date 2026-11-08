<?php

namespace App\Services;

use App\Enums\FulfillmentType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\ProductVariant;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public const MAX_PENDING_PER_PHONE = 3;

    /** Kunci advisory PostgreSQL: pembuatan pesanan diproses satu per satu (nomor pesanan aman). */
    private const LOCK_KEY = 7340211;

    public function __construct(private CartService $cart) {}

    /**
     * Membuat pesanan dari isi keranjang session. Harga, stok, dan ongkir
     * SELALU dibaca dari database; tidak ada nilai dari browser yang dipercaya.
     *
     * @param  array{customer_name: string, whatsapp: string, fulfillment_type: FulfillmentType, payment_method: PaymentMethod, delivery_address: ?string, customer_notes: ?string}  $data
     */
    public function place(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            DB::select('select pg_advisory_xact_lock(?)', [self::LOCK_KEY]);

            $cart = $this->cart->raw();

            if ($cart === []) {
                throw ValidationException::withMessages(['cart' => 'Keranjang Anda kosong.']);
            }

            $pending = Order::where('whatsapp', $data['whatsapp'])
                ->where('status', OrderStatus::Pending->value)
                ->count();

            if ($pending >= self::MAX_PENDING_PER_PHONE) {
                throw ValidationException::withMessages([
                    'cart' => 'Nomor WhatsApp ini masih memiliki '.self::MAX_PENDING_PER_PHONE
                        .' pesanan yang menunggu konfirmasi. Mohon tunggu konfirmasi dari toko.',
                ]);
            }

            // Kunci baris varian (urut ID agar tidak terjadi deadlock) lalu cek ulang stok.
            $ids = array_keys($cart);
            sort($ids);

            $variants = ProductVariant::whereIn('id', $ids)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->load('product.brand', 'product.category')
                ->keyBy('id');

            $problems = [];

            foreach ($cart as $id => $quantity) {
                $variant = $variants->get($id);

                if (! $variant || ! $this->cart->isPurchasable($variant)) {
                    $problems[] = ($variant ? "{$variant->product->name} {$variant->size_ml} ml" : 'Sebuah produk').' sudah tidak tersedia.';
                } elseif ($variant->stock < $quantity) {
                    $problems[] = "Stok {$variant->product->name} {$variant->size_ml} ml tidak cukup (tersisa {$variant->stock}).";
                }
            }

            if ($problems !== []) {
                throw ValidationException::withMessages(['cart' => $problems]);
            }

            // Hitung dari harga di database.
            $subtotal = 0;
            $lines = [];

            foreach ($cart as $id => $quantity) {
                $variant = $variants->get($id);
                $lineTotal = $variant->price * $quantity;
                $subtotal += $lineTotal;

                $lines[] = [
                    'variant' => $variant,
                    'quantity' => $quantity,
                    'line_total' => $lineTotal,
                ];
            }

            $isDelivery = $data['fulfillment_type'] === FulfillmentType::Delivery;
            $shippingFee = $isDelivery ? max(0, (int) Setting::get('shipping_fee', '0')) : 0;

            $order = Order::create([
                'order_number' => $this->nextOrderNumber(),
                'customer_name' => $data['customer_name'],
                'whatsapp' => $data['whatsapp'],
                'fulfillment_type' => $data['fulfillment_type'],
                'payment_method' => $data['payment_method'],
                'delivery_address' => $isDelivery ? $data['delivery_address'] : null,
                'customer_notes' => $data['customer_notes'],
                'subtotal' => $subtotal,
                'shipping_fee' => $shippingFee,
                'total' => $subtotal + $shippingFee,
                'status' => OrderStatus::Pending,
            ]);

            foreach ($lines as $line) {
                /** @var ProductVariant $variant */
                $variant = $line['variant'];

                // Pengurangan bersyarat: tidak akan pernah membuat stok negatif.
                $affected = ProductVariant::whereKey($variant->id)
                    ->where('stock', '>=', $line['quantity'])
                    ->decrement('stock', $line['quantity']);

                if ($affected !== 1) {
                    throw ValidationException::withMessages([
                        'cart' => "Stok {$variant->product->name} {$variant->size_ml} ml baru saja berubah. Silakan periksa keranjang Anda.",
                    ]);
                }

                // Snapshot: riwayat tidak berubah walau produk diedit atau dihapus.
                $order->items()->create([
                    'product_variant_id' => $variant->id,
                    'brand_name' => $variant->product->brand->name,
                    'product_name' => $variant->product->name,
                    'size_ml' => $variant->size_ml,
                    'unit_price' => $variant->price,
                    'quantity' => $line['quantity'],
                    'line_total' => $line['line_total'],
                ]);
            }

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'user_id' => null,
                'status' => OrderStatus::Pending,
                'note' => 'Pesanan dibuat oleh pelanggan.',
            ]);

            return $order->load('items');
        });
    }

    private function nextOrderNumber(): string
    {
        $prefix = 'VNR-'.now()->format('Ymd').'-';

        $last = Order::where('order_number', 'like', $prefix.'%')
            ->orderByDesc('id')
            ->value('order_number');

        $next = $last ? ((int) Str::afterLast($last, '-')) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
