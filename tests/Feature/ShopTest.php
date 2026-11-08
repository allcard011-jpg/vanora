<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ShopTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function category(string $name = 'Unisex', bool $active = true): Category
    {
        return Category::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'is_active' => $active]);
    }

    /** @param array<int, array{0:int,1:int,2:int}> $variants [ukuran, harga, stok] */
    private function product(string $name, array $variants = [[5, 40000, 10]], array $attrs = [], ?Category $category = null): Product
    {
        $product = Product::create($attrs + [
            'brand_id' => Brand::firstOrCreate(['slug' => 'mykonos'], ['name' => 'Mykonos'])->id,
            'category_id' => ($category ?? $this->category())->id,
            'name' => $name,
            'slug' => Str::slug('mykonos '.$name),
        ]);

        foreach ($variants as [$size, $price, $stock]) {
            $product->variants()->create(['size_ml' => $size, 'price' => $price, 'stock' => $stock]);
        }

        return $product;
    }

    private function variant(int $stock = 10, int $price = 40000): ProductVariant
    {
        return $this->product('Parfum Uji '.Str::random(5), [[5, $price, $stock]])->variants()->firstOrFail();
    }

    public function test_beranda_dan_katalog_dapat_dibuka(): void
    {
        $this->product('Alfa Aktif', attrs: ['is_featured' => true]);

        $this->get('/')->assertOk()->assertSee('Alfa Aktif')->assertSee('Mulai Rp40.000');
        $this->get('/katalog')->assertOk()->assertSee('Alfa Aktif');
    }

    public function test_katalog_hanya_menampilkan_produk_aktif_dan_tersedia(): void
    {
        $this->product('Alfa Aktif');
        $this->product('Beta Nonaktif', attrs: ['is_active' => false]);
        $this->product('Gama Habis', [[5, 40000, 0]]);
        $this->product('Delta VarianMati')->variants()->update(['is_active' => false]);
        $this->product('Epsilon KategoriMati', category: $this->category('Tersembunyi', false));

        $this->get('/katalog')
            ->assertOk()
            ->assertSee('Alfa Aktif')
            ->assertDontSee('Beta Nonaktif')
            ->assertDontSee('Gama Habis')
            ->assertDontSee('Delta VarianMati')
            ->assertDontSee('Epsilon KategoriMati');
    }

    public function test_halaman_produk_hanya_untuk_produk_tersedia(): void
    {
        $this->product('Alfa Aktif');
        $this->product('Beta Nonaktif', attrs: ['is_active' => false]);

        $this->get('/produk/mykonos-alfa-aktif')->assertOk()->assertSee('Rp40.000')->assertSee('5 ml');
        $this->get('/produk/mykonos-beta-nonaktif')->assertNotFound();
        $this->get('/produk/tidak-ada')->assertNotFound();
    }

    public function test_pencarian_tidak_menganggap_persen_sebagai_wildcard(): void
    {
        $this->product('Vanilla Clouds');
        $this->product('Cafe Drops');

        $this->get('/katalog?q=vanilla')->assertSee('Vanilla Clouds')->assertDontSee('Cafe Drops');
        $this->get('/katalog?q=%25')->assertDontSee('Vanilla Clouds')->assertDontSee('Cafe Drops');
    }

    public function test_urutan_harga(): void
    {
        $this->product('Murah', [[5, 20000, 5]]);
        $this->product('Mahal', [[5, 90000, 5]]);

        $this->get('/katalog?urut=harga-terendah')->assertSeeInOrder(['Murah', 'Mahal']);
        $this->get('/katalog?urut=harga-tertinggi')->assertSeeInOrder(['Mahal', 'Murah']);
    }

    public function test_filter_kategori(): void
    {
        $this->product('Produk Pria', category: $this->category('Pria'));
        $this->product('Produk Wanita', category: $this->category('Wanita'));

        $this->get('/katalog?kategori=pria')->assertSee('Produk Pria')->assertDontSee('Produk Wanita');
    }

    public function test_keranjang_menyimpan_id_dan_jumlah_bukan_harga(): void
    {
        $variant = $this->variant(10, 40000);

        $this->post('/keranjang', ['variant_id' => $variant->id, 'quantity' => 2, 'price' => 1])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('cart', [$variant->id => 2]);

        $this->get('/keranjang')->assertOk()->assertSee('Rp80.000');
    }

    public function test_jumlah_digabung_dan_tidak_melebihi_stok(): void
    {
        $variant = $this->variant(3);

        $this->post('/keranjang', ['variant_id' => $variant->id, 'quantity' => 2]);
        $this->post('/keranjang', ['variant_id' => $variant->id, 'quantity' => 2])
            ->assertSessionHasErrors('quantity');

        $this->assertSame([$variant->id => 2], session('cart'));
    }

    public function test_varian_tidak_tersedia_ditolak(): void
    {
        $variant = $this->variant();
        $variant->update(['is_active' => false]);

        $this->post('/keranjang', ['variant_id' => $variant->id, 'quantity' => 1])
            ->assertSessionHasErrors('quantity');

        $this->assertNull(session('cart'));
    }

    public function test_validasi_input_keranjang(): void
    {
        $variant = $this->variant();

        foreach ([0, 21, 'abc', ''] as $quantity) {
            $this->post('/keranjang', ['variant_id' => $variant->id, 'quantity' => $quantity])
                ->assertSessionHasErrors('quantity');
        }

        $this->post('/keranjang', ['variant_id' => 999999, 'quantity' => 1])
            ->assertSessionHasErrors('variant_id');

        $this->assertNull(session('cart'));
    }

    public function test_ubah_jumlah_keranjang(): void
    {
        $variant = $this->variant(10);

        $this->withSession(['cart' => [$variant->id => 1]])
            ->patch("/keranjang/{$variant->id}", ['quantity' => 4])
            ->assertRedirect(route('cart.show'))
            ->assertSessionHas('cart', [$variant->id => 4]);

        $this->patch("/keranjang/{$variant->id}", ['quantity' => 99])->assertSessionHasErrors('quantity');
        $this->assertSame([$variant->id => 4], session('cart'));

        $this->patch("/keranjang/{$variant->id}", ['quantity' => 0])->assertSessionHas('cart', []);
    }

    public function test_hapus_dari_keranjang(): void
    {
        $variant = $this->variant();

        $this->withSession(['cart' => [$variant->id => 2]])
            ->delete("/keranjang/{$variant->id}")
            ->assertRedirect(route('cart.show'))
            ->assertSessionHas('cart', []);
    }

    public function test_keranjang_dikoreksi_saat_stok_berkurang(): void
    {
        $variant = $this->variant(10);
        $variant->update(['stock' => 2]);

        $this->withSession(['cart' => [$variant->id => 5]])
            ->get('/keranjang')
            ->assertOk()
            ->assertSee('disesuaikan')
            ->assertSessionHas('cart', [$variant->id => 2]);
    }

    public function test_varian_nonaktif_dibuang_dari_keranjang(): void
    {
        $variant = $this->variant();
        $variant->update(['is_active' => false]);

        $this->withSession(['cart' => [$variant->id => 1]])
            ->get('/keranjang')
            ->assertOk()
            ->assertSee('tidak tersedia')
            ->assertSessionHas('cart', []);
    }
}
