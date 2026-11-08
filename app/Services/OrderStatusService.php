<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderStatusService
{
    /** Langkah berikutnya dalam alur pesanan, atau null jika sudah status akhir. */
    public function nextStatus(Order $order): ?OrderStatus
    {
        $flow = OrderStatus::flow($order->fulfillment_type);
        $index = array_search($order->status, $flow, true);

        return $index === false ? null : ($flow[$index + 1] ?? null);
    }

    /**
     * Maju satu langkah. $expectedStatus adalah status yang dilihat admin saat
     * membuka halaman; jika sudah berubah, permintaan ditolak (halaman basi).
     */
    public function advance(int $orderId, User $admin, string $expectedStatus, ?string $note = null): Order
    {
        return DB::transaction(function () use ($orderId, $admin, $expectedStatus, $note) {
            $order = $this->lockAndCheck($orderId, $expectedStatus);
            $next = $this->nextStatus($order);

            if ($next === null) {
                throw ValidationException::withMessages([
                    'status' => 'Pesanan ini sudah berstatus akhir dan tidak bisa diubah lagi.',
                ]);
            }

            $order->update(['status' => $next]);
            $this->record($order, $admin, $next, $note);

            return $order;
        });
    }

    /** Batalkan pesanan, kembalikan stok (sekali saja), dan catat alasannya. */
    public function cancel(int $orderId, User $admin, string $expectedStatus, string $reason): Order
    {
        return DB::transaction(function () use ($orderId, $admin, $expectedStatus, $reason) {
            $order = $this->lockAndCheck($orderId, $expectedStatus);

            if ($order->status->isFinal()) {
                throw ValidationException::withMessages([
                    'status' => 'Pesanan yang sudah selesai atau dibatalkan tidak bisa dibatalkan lagi.',
                ]);
            }

            $items = $order->items()->get();
            $restorable = $items->whereNotNull('product_variant_id');

            // Kunci varian berurutan ID (hindari deadlock), lalu kembalikan stok.
            $ids = $restorable->pluck('product_variant_id')->unique()->sort()->values()->all();

            if ($ids !== []) {
                ProductVariant::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
            }

            foreach ($restorable as $item) {
                ProductVariant::whereKey($item->product_variant_id)->increment('stock', $item->quantity);
            }

            $order->update([
                'status' => OrderStatus::Cancelled,
                'cancel_reason' => $reason,
            ]);

            $note = 'Dibatalkan: '.$reason;
            $lost = $items->count() - $restorable->count();

            if ($lost > 0) {
                $note .= " ({$lost} item tidak dikembalikan ke stok karena produknya sudah dihapus)";
            }

            $this->record($order, $admin, OrderStatus::Cancelled, $note);

            return $order;
        });
    }

    private function lockAndCheck(int $orderId, string $expectedStatus): Order
    {
        $order = Order::whereKey($orderId)->lockForUpdate()->firstOrFail();

        if ($order->status->value !== $expectedStatus) {
            throw ValidationException::withMessages([
                'status' => "Status pesanan sudah berubah menjadi \"{$order->status->label()}\". Muat ulang halaman lalu coba lagi.",
            ]);
        }

        return $order;
    }

    private function record(Order $order, User $admin, OrderStatus $status, ?string $note): void
    {
        OrderStatusHistory::create([
            'order_id' => $order->id,
            'user_id' => $admin->id,
            'status' => $status,
            'note' => $note !== null && trim($note) !== '' ? trim($note) : null,
        ]);
    }
}
