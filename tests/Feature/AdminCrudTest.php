<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->actingAs(User::factory()->create());
    }

    private function product(): Product
    {
        return Product::create([
            'brand_id' => Brand::firstOrCreate(['slug' => 'mykonos'], ['name' => 'Mykonos'])->id,
            'category_id' => Category::firstOrCreate(['slug' => 'unisex'], ['name' => 'Unisex'])->id,
            'name' => 'Tes Parfum',
            'slug' => 'mykonos-tes-parfum',
        ]);
    }

    public function test_admin_dapat_menambah_mengubah_dan_menghapus_merek(): void
    {
        $this->post('/admin/brands', ['name' => 'Nikos'])->assertRedirect(route('admin.brands.index'));
        $this->assertDatabaseHas('brands', ['name' => 'Nikos', 'slug' => 'nikos']);

        $brand = Brand::where('name', 'Nikos')->firstOrFail();

        $this->put("/admin/brands/{$brand->id}", ['name' => 'Nikos Sculpture'])
            ->assertRedirect(route('admin.brands.index'));
        $this->assertDatabaseHas('brands', ['id' => $brand->id, 'name' => 'Nikos Sculpture']);

        $this->delete("/admin/brands/{$brand->id}")->assertRedirect(route('admin.brands.index'));
        $this->assertDatabaseMissing('brands', ['id' => $brand->id]);
    }

    public function test_merek_yang_dipakai_produk_tidak_dapat_dihapus(): void
    {
        $product = $this->product();

        $this->delete('/admin/brands/'.$product->brand_id)->assertSessionHas('error');

        $this->assertDatabaseHas('brands', ['id' => $product->brand_id]);
    }

    public function test_nama_merek_ganda_ditolak(): void
    {
        Brand::create(['name' => 'Mykonos', 'slug' => 'mykonos']);

        $this->post('/admin/brands', ['name' => 'Mykonos'])->assertSessionHasErrors('name');

        $this->assertSame(1, Brand::count());
    }

    public function test_admin_dapat_mengubah_kategori_dan_menonaktifkannya(): void
    {
        $category = Category::create(['name' => 'Pria', 'slug' => 'pria']);

        $this->put("/admin/categories/{$category->id}", ['name' => 'Pria Dewasa', 'is_active' => 0])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('categories', [
            'id' => $category->id, 'name' => 'Pria Dewasa', 'slug' => 'pria', 'is_active' => false,
        ]);
    }

    public function test_admin_dapat_mengubah_produk(): void
    {
        $product = $this->product();
        $category = Category::create(['name' => 'Pria', 'slug' => 'pria']);

        $this->put("/admin/products/{$product->id}", [
            'name' => 'Tes Parfum Baru',
            'brand_id' => $product->brand_id,
            'category_id' => $category->id,
            'description' => 'Deskripsi baru',
            'top_notes' => 'Bergamot',
            'middle_notes' => 'Rose',
            'base_notes' => 'Musk',
            'image_path' => 'images/products/tes.jpg',
            'is_active' => 0,
            'is_featured' => 1,
        ])->assertSessionHasNoErrors()->assertRedirect(route('admin.products.edit', $product));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Tes Parfum Baru',
            'slug' => 'mykonos-tes-parfum',
            'category_id' => $category->id,
            'is_active' => false,
            'is_featured' => true,
            'image_path' => 'images/products/tes.jpg',
        ]);
    }

    public function test_nama_produk_ganda_pada_merek_yang_sama_ditolak(): void
    {
        $product = $this->product();

        $this->post('/admin/products', [
            'name' => 'Tes Parfum',
            'brand_id' => $product->brand_id,
            'category_id' => $product->category_id,
        ])->assertSessionHasErrors('name');

        $this->assertSame(1, Product::count());
    }

    public function test_admin_dapat_menghapus_produk_beserta_varian(): void
    {
        $product = $this->product();
        $product->variants()->create(['size_ml' => 5, 'price' => 40000, 'stock' => 3]);

        $this->delete("/admin/products/{$product->id}")->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
        $this->assertDatabaseCount('product_variants', 0);
    }

    public function test_hapus_produk_tidak_menghapus_riwayat_pesanan(): void
    {
        $product = $this->product();
        $variant = $product->variants()->create(['size_ml' => 5, 'price' => 40000, 'stock' => 3]);

        $order = Order::create([
            'order_number' => 'VNR-TES-0001',
            'customer_name' => 'Tes',
            'whatsapp' => '6281234567890',
            'fulfillment_type' => 'pickup',
            'payment_method' => 'cash',
            'subtotal' => 40000,
            'shipping_fee' => 0,
            'total' => 40000,
            'status' => 'pending',
        ]);
        $order->items()->create([
            'product_variant_id' => $variant->id,
            'brand_name' => 'Mykonos',
            'product_name' => 'Tes Parfum',
            'size_ml' => 5,
            'unit_price' => 40000,
            'quantity' => 1,
            'line_total' => 40000,
        ]);

        $this->delete("/admin/products/{$product->id}");

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_name' => 'Tes Parfum',
            'unit_price' => 40000,
            'product_variant_id' => null,
        ]);
    }

    public function test_admin_dapat_menghapus_varian(): void
    {
        $variant = $this->product()->variants()->create(['size_ml' => 5, 'price' => 40000, 'stock' => 3]);

        $this->delete("/admin/variants/{$variant->id}")->assertRedirect();

        $this->assertDatabaseMissing('product_variants', ['id' => $variant->id]);
    }
}
