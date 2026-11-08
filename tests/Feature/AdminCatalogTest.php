<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function login(): void
    {
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

    private function settingsPayload(array $override = []): array
    {
        return $override + [
            'store_name' => 'Vanora Parfum',
            'store_address' => 'Minggu Raya',
            'store_whatsapp' => '0851-2404-8406',
            'opening_hours' => 'Selasa-Minggu 19.00-22.00',
            'delivery_area' => 'Banjarbaru',
            'shipping_fee' => '15000',
            'payment_info_cash' => 'Bayar tunai.',
            'payment_info_qris' => 'Bayar QRIS.',
            'qris_image_path' => 'images/qris.png',
        ];
    }

    public function test_tamu_diarahkan_ke_login_untuk_halaman_katalog_admin(): void
    {
        foreach (['/admin/products', '/admin/categories', '/admin/brands', '/admin/settings'] as $url) {
            $this->get($url)->assertRedirect(route('admin.login'));
        }
    }

    public function test_admin_dapat_menambah_kategori(): void
    {
        $this->login();

        $this->post('/admin/categories', ['name' => 'Anak', 'is_active' => 1])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('categories', ['name' => 'Anak', 'slug' => 'anak', 'is_active' => true]);
    }

    public function test_kategori_yang_dipakai_produk_tidak_dapat_dihapus(): void
    {
        $this->login();
        $product = $this->product();

        $this->delete('/admin/categories/'.$product->category_id)->assertSessionHas('error');

        $this->assertDatabaseHas('categories', ['id' => $product->category_id]);
    }

    public function test_admin_membuat_produk_lalu_menambah_varian(): void
    {
        $this->login();
        $brand = Brand::create(['name' => 'Mykonos', 'slug' => 'mykonos']);
        $category = Category::create(['name' => 'Pria', 'slug' => 'pria']);

        $this->post('/admin/products', [
            'name' => 'Tes Parfum', 'brand_id' => $brand->id, 'category_id' => $category->id,
            'is_active' => 1, 'is_featured' => 0,
        ])->assertRedirect();

        $product = Product::firstOrFail();
        $this->assertSame('mykonos-tes-parfum', $product->slug);

        $this->post("/admin/products/{$product->id}/variants", [
            'size_ml' => 5, 'price' => 40000, 'stock' => 7, 'is_active' => 1,
        ])->assertRedirect(route('admin.products.edit', $product));

        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id, 'size_ml' => 5, 'price' => 40000, 'stock' => 7,
        ]);
    }

    public function test_stok_negatif_ditolak_validasi_varian(): void
    {
        $this->login();
        $product = $this->product();

        $this->post("/admin/products/{$product->id}/variants", [
            'size_ml' => 5, 'price' => 40000, 'stock' => -1,
        ])->assertSessionHasErrors('stock', null, 'newVariant');

        $this->assertDatabaseCount('product_variants', 0);
    }

    public function test_ukuran_ganda_ditolak_validasi(): void
    {
        $this->login();
        $product = $this->product();
        $product->variants()->create(['size_ml' => 5, 'price' => 40000, 'stock' => 3]);

        $this->post("/admin/products/{$product->id}/variants", [
            'size_ml' => 5, 'price' => 41000, 'stock' => 3,
        ])->assertSessionHasErrors('size_ml', null, 'newVariant');

        $this->assertDatabaseCount('product_variants', 1);
    }

    public function test_admin_dapat_mengubah_harga_dan_stok_varian(): void
    {
        $this->login();
        $variant = $this->product()->variants()->create(['size_ml' => 5, 'price' => 40000, 'stock' => 3]);

        $this->put("/admin/variants/{$variant->id}", ['price' => 50000, 'stock' => 9, 'is_active' => 0])
            ->assertRedirect();

        $this->assertDatabaseHas('product_variants', [
            'id' => $variant->id, 'price' => 50000, 'stock' => 9, 'is_active' => false,
        ]);
    }

    public function test_daftar_produk_menampilkan_rentang_harga(): void
    {
        $this->login();
        $product = $this->product();
        $product->variants()->create(['size_ml' => 2, 'price' => 20000, 'stock' => 4]);
        $product->variants()->create(['size_ml' => 10, 'price' => 75000, 'stock' => 6]);

        $this->get('/admin/products')
            ->assertOk()
            ->assertSee('Tes Parfum')
            ->assertSee('Rp20.000')
            ->assertSee('Rp75.000');
    }

    public function test_path_gambar_berbahaya_ditolak(): void
    {
        $this->login();
        $brand = Brand::create(['name' => 'Mykonos', 'slug' => 'mykonos']);
        $category = Category::create(['name' => 'Pria', 'slug' => 'pria']);
        $base = ['name' => 'Tes', 'brand_id' => $brand->id, 'category_id' => $category->id];

        $this->post('/admin/products', $base + ['image_path' => 'javascript:alert(1)'])
            ->assertSessionHasErrors('image_path');
        $this->post('/admin/products', $base + ['image_path' => 'images/../../.env'])
            ->assertSessionHasErrors('image_path');

        $this->assertDatabaseCount('products', 0);

        $this->post('/admin/products', $base + ['image_path' => 'images/products/tes.jpg'])
            ->assertSessionDoesntHaveErrors();

        $this->assertDatabaseHas('products', ['image_path' => 'images/products/tes.jpg']);
    }

    public function test_pengaturan_disimpan_dan_nomor_wa_dinormalisasi(): void
    {
        $this->login();

        $this->put('/admin/settings', $this->settingsPayload())->assertRedirect(route('admin.settings.edit'));

        $this->assertSame('6285124048406', Setting::get('store_whatsapp'));
        $this->assertSame('15000', Setting::get('shipping_fee'));
    }

    public function test_nomor_wa_tidak_valid_ditolak(): void
    {
        $this->login();

        $this->put('/admin/settings', $this->settingsPayload(['store_whatsapp' => '12345']))
            ->assertSessionHasErrors('store_whatsapp');

        $this->assertNull(Setting::get('store_whatsapp'));
    }
}
