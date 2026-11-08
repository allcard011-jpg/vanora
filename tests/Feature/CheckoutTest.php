<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Support\WhatsApp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        Setting::put('shipping_fee', '10000');
        Setting::put('store_whatsapp', '6285124048406');
    }

    private function variant(int $stock = 10, int $price = 40000): ProductVariant
    {
        $product = Product::create([
            'brand_id' => Brand::firstOrCreate(['slug' => 'mykonos'], ['name' => 'Mykonos'])->id,
            'category_id' => Category::firstOrCreate(['slug' => 'unisex'], ['name' => 'Unisex'])->id,
            'name' => 'Parfum Uji '.Str::random(5),
            'slug' => 'uji-'.Str::lower(Str::random(8)),
        ]);

        return $product->variants()->create(['size_ml' => 5, 'price' => $price, 'stock' => $stock]);
    }

    private function payload(array $override = []): array
    {
        return $override + [
            'customer_name' => 'Budi Santoso',
            'whatsapp' => '081234567890',
            'fulfillment_type' => 'pickup',
            'payment_method' => 'cash',
        ];
    }

    private function checkout(ProductVariant $variant, int $quantity, array $override = [])
    {
        return $this->withSession(['cart' => [$variant->id => $quantity]])
            ->post('/checkout', $this->payload($override));
    }

    public function test_checkout_tanpa_isi_keranjang_diarahkan_ke_keranjang(): void
    {
        $this->get('/checkout')->assertRedirect(route('cart.show'));
    }

    public function test_halaman_checkout_menampilkan_ringkasan(): void
    {
        $variant = $this->variant(10, 40000);

        $this->withSession(['cart' => [$variant->id => 2]])
            ->get('/checkout')
            ->assertOk()
            ->assertSee('Rp80.000')
            ->assertSee('Pesan Sekarang');
    }

    public function test_pesanan_pickup_berhasil_dibuat(): void
    {
        $variant = $this->variant(10, 40000);

        $response = $this->checkout($variant, 2);

        $order = Order::firstOrFail();
        $response->assertRedirect(route('orders.show', $order->order_number));

        $this->assertSame(80000, $order->subtotal);
        $this->assertSame(0, $order->shipping_fee);
        $this->assertSame(80000, $order->total);
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertSame('6281234567890', $order->whatsapp);
        $this->assertNull($order->delivery_address);

        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_variant_id' => $variant->id,
            'size_ml' => 5,
            'unit_price' => 40000,
            'quantity' => 2,
            'line_total' => 80000,
        ]);
        $this->assertDatabaseHas('order_status_histories', ['order_id' => $order->id, 'status' => 'pending']);
        $this->assertSame(8, $variant->fresh()->stock);
        $this->assertNull(session('cart'));
    }

    public function test_pesanan_delivery_memakai_ongkir_dari_server_dan_mengabaikan_nilai_browser(): void
    {
        $variant = $this->variant(10, 40000);

        $this->checkout($variant, 1, [
            'fulfillment_type' => 'delivery',
            'delivery_address' => 'Jl. Ahmad Yani KM 33 No. 5, Banjarbaru',
            'confirm_area' => '1',
            // Upaya manipulasi dari browser:
            'shipping_fee' => 0,
            'total' => 1,
            'subtotal' => 1,
            'price' => 1,
        ])->assertSessionHasNoErrors();

        $order = Order::firstOrFail();
        $this->assertSame(40000, $order->subtotal);
        $this->assertSame(10000, $order->shipping_fee);
        $this->assertSame(50000, $order->total);
        $this->assertSame('Jl. Ahmad Yani KM 33 No. 5, Banjarbaru', $order->delivery_address);
    }

    public function test_delivery_wajib_alamat_dan_pernyataan_wilayah(): void
    {
        $variant = $this->variant(10);

        $this->checkout($variant, 1, ['fulfillment_type' => 'delivery'])
            ->assertSessionHasErrors(['delivery_address', 'confirm_area']);

        $this->assertSame(0, Order::count());
        $this->assertSame(10, $variant->fresh()->stock);
        $this->assertSame([$variant->id => 1], session('cart'));
    }

    public function test_nomor_whatsapp_tidak_valid_ditolak(): void
    {
        $variant = $this->variant(10);

        foreach (['123', 'abc', '021123456'] as $number) {
            $this->checkout($variant, 1, ['whatsapp' => $number])->assertSessionHasErrors('whatsapp');
        }

        $this->assertSame(0, Order::count());
    }

    public function test_jenis_dan_metode_pembayaran_tidak_valid_ditolak(): void
    {
        $variant = $this->variant(10);

        $this->checkout($variant, 1, ['fulfillment_type' => 'kurir', 'payment_method' => 'kartu'])
            ->assertSessionHasErrors(['fulfillment_type', 'payment_method']);

        $this->assertSame(0, Order::count());
    }

    public function test_stok_tidak_cukup_membatalkan_pesanan(): void
    {
        $variant = $this->variant(3);

        $this->checkout($variant, 5)->assertSessionHasErrors('cart');

        $this->assertSame(0, Order::count());
        $this->assertSame(3, $variant->fresh()->stock);
    }

    public function test_stok_terakhir_tidak_bisa_terjual_dua_kali(): void
    {
        $variant = $this->variant(1);

        $this->checkout($variant, 1)->assertSessionHasNoErrors();
        $this->checkout($variant, 1, ['whatsapp' => '081111111111'])->assertSessionHasErrors('cart');

        $this->assertSame(1, Order::count());
        $this->assertSame(0, $variant->fresh()->stock);
    }

    public function test_nomor_pesanan_unik_dan_berurutan(): void
    {
        $variant = $this->variant(10);

        $this->checkout($variant, 1);
        $this->checkout($variant, 1);

        $prefix = 'VNR-'.now()->format('Ymd').'-';
        $this->assertSame(
            [$prefix.'0001', $prefix.'0002'],
            Order::orderBy('id')->pluck('order_number')->all()
        );
    }

    public function test_batas_pesanan_pending_per_nomor_whatsapp(): void
    {
        $variant = $this->variant(20);

        for ($i = 0; $i < 3; $i++) {
            $this->checkout($variant, 1)->assertSessionHasNoErrors();
        }

        $this->checkout($variant, 1)->assertSessionHasErrors('cart');

        $this->assertSame(3, Order::count());
        $this->assertSame(17, $variant->fresh()->stock);
    }

    public function test_riwayat_pesanan_tidak_berubah_saat_produk_diedit(): void
    {
        $variant = $this->variant(10, 40000);
        $originalName = $variant->product->name;

        $this->checkout($variant, 1);

        $variant->update(['price' => 99999]);
        $variant->product->update(['name' => 'Nama Baru']);

        $this->assertDatabaseHas('order_items', [
            'product_name' => $originalName,
            'unit_price' => 40000,
            'line_total' => 40000,
        ]);
    }

    public function test_nama_berisi_html_di_escape_pada_halaman_pesanan(): void
    {
        $variant = $this->variant(10);

        $this->checkout($variant, 1, ['customer_name' => '<b>Budi</b>']);
        $order = Order::firstOrFail();

        $this->get(route('orders.show', $order->order_number))
            ->assertOk()
            ->assertDontSee('<b>Budi</b>', false)
            ->assertSee('&lt;b&gt;Budi&lt;/b&gt;', false);
    }

    public function test_cek_pesanan_dengan_nomor_dan_whatsapp(): void
    {
        $this->checkout($this->variant(10), 1);
        $order = Order::firstOrFail();

        // Session baru: belum boleh melihat.
        $this->flushSession();
        $this->get(route('orders.show', $order->order_number))->assertRedirect(route('orders.track'));

        $this->post('/cek-pesanan', [
            'order_number' => Str::lower($order->order_number),
            'whatsapp' => '0812-3456-7890',
        ])->assertRedirect(route('orders.show', $order->order_number));

        $this->get(route('orders.show', $order->order_number))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('Menunggu Konfirmasi');
    }

    public function test_cek_pesanan_ditolak_untuk_whatsapp_salah(): void
    {
        $this->checkout($this->variant(10), 1);
        $order = Order::firstOrFail();
        $this->flushSession();

        $this->post('/cek-pesanan', ['order_number' => $order->order_number, 'whatsapp' => '089999999999'])
            ->assertSessionHasErrors('order_number');

        $this->get(route('orders.show', $order->order_number))->assertRedirect(route('orders.track'));
    }

    public function test_cek_pesanan_dibatasi_setelah_banyak_percobaan_gagal(): void
    {
        $this->checkout($this->variant(10), 1);
        $order = Order::firstOrFail();
        $this->flushSession();

        for ($i = 0; $i < 10; $i++) {
            $this->post('/cek-pesanan', ['order_number' => $order->order_number, 'whatsapp' => '089999999999']);
        }

        // Percobaan ke-11 dengan data BENAR tetap harus diblokir.
        $this->post('/cek-pesanan', ['order_number' => $order->order_number, 'whatsapp' => '081234567890'])
            ->assertSessionHasErrors('order_number');

        $this->assertStringContainsString('Terlalu banyak', session('errors')->first('order_number'));
    }

    public function test_tautan_whatsapp_berisi_nomor_toko_dan_ringkasan_pesanan(): void
    {
        $this->checkout($this->variant(10, 40000), 2);
        $order = Order::firstOrFail();

        $url = WhatsApp::orderUrl($order, '6285124048406');
        $text = urldecode(Str::after($url, 'text='));

        $this->assertStringStartsWith('https://wa.me/6285124048406?text=', $url);
        $this->assertStringContainsString($order->order_number, $text);
        $this->assertStringContainsString('x2 = Rp80.000', $text);
        $this->assertStringContainsString('Total: Rp80.000', $text);
    }
}
