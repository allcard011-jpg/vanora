<?php

namespace App\Support;

use App\Enums\FulfillmentType;
use App\Models\Order;

class WhatsApp
{
    /** Tautan wa.me ke toko dengan pesan pesanan yang sudah terisi. */
    public static function orderUrl(Order $order, string $storeNumber): string
    {
        $order->loadMissing('items');

        $lines = [
            'Halo Vanora, saya ingin mengonfirmasi pesanan:',
            '',
            'No. Pesanan: '.$order->order_number,
            'Nama: '.$order->customer_name,
            '',
            'Pesanan:',
        ];

        foreach ($order->items as $item) {
            $lines[] = "- {$item->brand_name} {$item->product_name} {$item->size_ml} ml x{$item->quantity} = ".Rupiah::format($item->line_total);
        }

        $lines[] = '';
        $lines[] = 'Subtotal: '.Rupiah::format($order->subtotal);

        if ($order->fulfillment_type === FulfillmentType::Delivery) {
            $lines[] = 'Ongkir: '.Rupiah::format($order->shipping_fee);
        }

        $lines[] = 'Total: '.Rupiah::format($order->total);
        $lines[] = '';
        $lines[] = 'Jenis: '.$order->fulfillment_type->label();

        if ($order->fulfillment_type === FulfillmentType::Delivery) {
            $lines[] = 'Alamat: '.$order->delivery_address;
        }

        $lines[] = 'Pembayaran: '.$order->payment_method->label();

        return 'https://wa.me/'.$storeNumber.'?text='.rawurlencode(implode("\n", $lines));
    }
}
