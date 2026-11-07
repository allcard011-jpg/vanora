<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('size_ml');
            $table->unsignedInteger('price');
            $table->integer('stock')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['product_id', 'size_ml']);
        });

        // Lapis pengaman kedua di database (PostgreSQL tidak punya kolom unsigned dari Laravel).
        DB::statement('ALTER TABLE product_variants ADD CONSTRAINT product_variants_stock_check CHECK (stock >= 0)');
        DB::statement('ALTER TABLE product_variants ADD CONSTRAINT product_variants_price_check CHECK (price >= 0)');
        DB::statement('ALTER TABLE product_variants ADD CONSTRAINT product_variants_size_check CHECK (size_ml > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
