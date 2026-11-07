<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 30)->unique();
            $table->string('customer_name', 100);
            $table->string('whatsapp', 20);
            $table->string('fulfillment_type', 20);
            $table->string('payment_method', 20);
            $table->text('delivery_address')->nullable();
            $table->text('customer_notes')->nullable();
            $table->text('delivery_notes')->nullable();
            $table->unsignedInteger('subtotal');
            $table->unsignedInteger('shipping_fee')->default(0);
            $table->unsignedInteger('total');
            $table->string('status', 30)->default('pending');
            $table->text('cancel_reason')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('created_at');
            $table->index('whatsapp');
        });

        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_fulfillment_check CHECK (fulfillment_type IN ('pickup', 'delivery'))");
        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_payment_check CHECK (payment_method IN ('cash', 'qris'))");
        DB::statement('ALTER TABLE orders ADD CONSTRAINT orders_total_check CHECK (total = subtotal + shipping_fee)');
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
