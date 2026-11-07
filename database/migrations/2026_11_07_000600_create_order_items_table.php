<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained()->nullOnDelete();
            // Snapshot: riwayat tidak berubah walau produk diedit atau dihapus.
            $table->string('brand_name', 80);
            $table->string('product_name', 120);
            $table->unsignedSmallInteger('size_ml');
            $table->unsignedInteger('unit_price');
            $table->unsignedSmallInteger('quantity');
            $table->unsignedInteger('line_total');
            $table->timestamps();
        });

        DB::statement('ALTER TABLE order_items ADD CONSTRAINT order_items_quantity_check CHECK (quantity > 0)');
        DB::statement('ALTER TABLE order_items ADD CONSTRAINT order_items_line_total_check CHECK (line_total = unit_price * quantity)');
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
