<?php

namespace App\Models;

use App\Enums\FulfillmentType;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'order_number', 'customer_name', 'whatsapp', 'fulfillment_type', 'payment_method',
        'delivery_address', 'customer_notes', 'delivery_notes',
        'subtotal', 'shipping_fee', 'total', 'status', 'cancel_reason',
    ];

    protected function casts(): array
    {
        return [
            'fulfillment_type' => FulfillmentType::class,
            'payment_method' => PaymentMethod::class,
            'status' => OrderStatus::class,
            'subtotal' => 'integer',
            'shipping_fee' => 'integer',
            'total' => 'integer',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at')->orderBy('id');
    }
}
