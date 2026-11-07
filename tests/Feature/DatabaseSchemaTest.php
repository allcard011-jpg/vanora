<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    private function makeVariant(): ProductVariant
    {
        $product = Product::create([
            'brand_id' => Brand::create(['name' => 'Mykonos', 'slug' => 'mykonos'])->id,
            'category_id' => Category::create(['name' => 'Unisex', 'slug' => 'unisex'])->id,
            'name' => 'Tes Parfum',
            'slug' => 'tes-parfum',
        ]);

        return $product->variants()->create(['size_ml' => 5, 'price' => 40000, 'stock' => 10]);
    }

    public function test_stok_negatif_ditolak_oleh_database(): void
    {
        $variant = $this->makeVariant();

        $this->expectException(QueryException::class);
        $variant->update(['stock' => -1]);
    }

    public function test_ukuran_ganda_pada_produk_yang_sama_ditolak(): void
    {
        $variant = $this->makeVariant();

        $this->expectException(QueryException::class);
        $variant->product->variants()->create(['size_ml' => 5, 'price' => 1000, 'stock' => 1]);
    }

    public function test_seeder_mengisi_data_awal(): void
    {
        config([
            'vanora.admin_email' => 'admin@vanora.test',
            'vanora.admin_password' => 'password-uji-12345',
        ]);

        $this->seed();

        $this->assertSame(13, Product::count());
        $this->assertSame(39, ProductVariant::count());
        $this->assertDatabaseHas('users', ['email' => 'admin@vanora.test']);
        $this->assertDatabaseHas('settings', ['key' => 'shipping_fee', 'value' => '10000']);
        $this->assertNotSame('password-uji-12345', \App\Models\User::first()->password);
    }
}
