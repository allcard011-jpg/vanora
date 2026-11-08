<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        Setting::put('shipping_fee', '10000');
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

    /** Membuat pesanan lewat alur checkout pelanggan yang sebenarnya. */
    private function placeOrder(ProductVariant $variant, int $quantity = 2, array $override = []): Order
    {
        $this->withSession(['cart' => [$variant->id => $quantity]])
            ->post('/checkout', $override + [
                'customer_name' => 'Budi Santoso',
                'whatsapp' => '081234567890',
                'fulfillment_type' => 'pickup',
                'payment_method' => 'cash',
            ])
            ->assertSessionHasNoErrors();

        return Order::orderByDesc('id')->firstOrFail();
    }

    private function deliveryPayload(): array
    {
        return [
            'fulfillment_type' => 'delivery',
            'delivery_address' => 'Jl. Ahmad Yani KM 33 No. 5, Banjarbaru',
            'confirm_area' => '1',
        ];
    }

    private function admin(): User
    {
        return User::factory()->create();
    }

    /** @return array<int, string> */
    private function historyStatuses(Order $order): array
    {
        return $order->histories()->get()->map(fn ($h) => $h->status->value)->all();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get('/admin/orders')->assertRedirect(route('admin.login'));
        $this->post('/admin/orders/1/advance', ['current_status' => 'pending'])->assertRedirect(route('admin.login'));
        $this->post('/admin/orders/1/cancel', ['current_status' => 'pending', 'reason' => 'tes tes'])->assertRedirect(route('admin.login'));
    }

    public function test_daftar_pesanan_dan_filter_status(): void
    {
        $pertama = $this->placeOrder($this->variant(), 1, ['customer_name' => 'Budi Pertama']);
        $kedua = $this->placeOrder($this->variant(), 1, ['customer_name' => 'Siti Kedua', 'whatsapp' => '089876543210']);
        $kedua->update(['status' => 'confirmed']);

        $this->actingAs($this->admin());

        $this->get('/admin/orders')->assertOk()->assertSee('Budi Pertama')->assertSee('Siti Kedua');

        $this->get('/admin/orders?status=confirmed')
            ->assertOk()
            ->assertSee($kedua->order_number)
            ->assertDontSee($pertama->order_number);
    }

    public function test_pencarian_berdasarkan_nama_dan_nomor_whatsapp(): void
    {
        $this->placeOrder($this->variant(), 1, ['customer_name' => 'Budi Pertama', 'whatsapp' => '081234567890']);
        $this->placeOrder($this->variant(), 1, ['customer_name' => 'Siti Kedua', 'whatsapp' => '089876543210']);

        $this->actingAs($this->admin());

        $this->get('/admin/orders?q=siti')->assertSee('Siti Kedua')->assertDontSee('Budi Pertama');
        $this->get('/admin/orders?q=0812345')->assertSee('Budi Pertama')->assertDontSee('Siti Kedua');
    }

    public function test_detail_pesanan_menampilkan_pelanggan_dan_item(): void
    {
        $order = $this->placeOrder($this->variant(10, 40000), 2);

        $this->actingAs($this->admin())
            ->get("/admin/orders/{$order->id}")
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('Budi Santoso')
            ->assertSee('Rp80.000')
            ->assertSee('Ubah ke: Dikonfirmasi');
    }

    public function test_nama_pelanggan_berisi_html_di_escape_di_admin(): void
    {
        $order = $this->placeOrder($this->variant(), 1, ['customer_name' => '<script>alert(1)</script>']);

        $this->actingAs($this->admin())
            ->get("/admin/orders/{$order->id}")
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_alur_status_pickup_sampai_selesai_dan_omzet_tercatat(): void
    {
        $admin = $this->admin();
        $order = $this->placeOrder($this->variant(10, 40000), 2);
        $this->actingAs($admin);

        foreach (['pending', 'confirmed', 'processing', 'ready_for_pickup'] as $current) {
            $this->post("/admin/orders/{$order->id}/advance", ['current_status' => $current])
                ->assertSessionHasNoErrors();
        }

        $this->assertSame(OrderStatus::Completed, $order->fresh()->status);
        $this->assertSame(
            ['pending', 'confirmed', 'processing', 'ready_for_pickup', 'completed'],
            $this->historyStatuses($order)
        );
        $this->assertSame($admin->id, $order->histories()->get()->last()->user_id);

        $this->get('/admin')->assertViewHas('stats', fn (array $s) => $s['revenue'] === 80000 && $s['completed'] === 1);
    }

    public function test_alur_status_delivery_melewati_sedang_diantar(): void
    {
        $order = $this->placeOrder($this->variant(), 1, $this->deliveryPayload());
        $this->actingAs($this->admin());

        foreach (['pending', 'confirmed', 'processing', 'out_for_delivery'] as $current) {
            $this->post("/admin/orders/{$order->id}/advance", ['current_status' => $current])
                ->assertSessionHasNoErrors();
        }

        $this->assertSame(
            ['pending', 'confirmed', 'processing', 'out_for_delivery', 'completed'],
            $this->historyStatuses($order)
        );
    }

    public function test_halaman_basi_dan_status_akhir_ditolak(): void
    {
        $order = $this->placeOrder($this->variant(), 1);
        $this->actingAs($this->admin());

        // Admin melihat "processing", padahal pesanan masih pending.
        $this->post("/admin/orders/{$order->id}/advance", ['current_status' => 'processing'])
            ->assertSessionHasErrors('status');
        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);

        // Status akhir tidak bisa dimajukan.
        $order->update(['status' => 'completed']);
        $this->post("/admin/orders/{$order->id}/advance", ['current_status' => 'completed'])
            ->assertSessionHasErrors('status');

        $order->update(['status' => 'cancelled']);
        $this->post("/admin/orders/{$order->id}/advance", ['current_status' => 'cancelled'])
            ->assertSessionHasErrors('status');
    }

    public function test_pembatalan_mengembalikan_stok_sekali_saja(): void
    {
        $variant = $this->variant(10);
        $order = $this->placeOrder($variant, 3);
        $this->assertSame(7, $variant->fresh()->stock);

        $this->actingAs($this->admin());
        $payload = ['current_status' => 'pending', 'reason' => 'Pelanggan membatalkan'];

        $this->post("/admin/orders/{$order->id}/cancel", $payload)->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertSame(OrderStatus::Cancelled, $order->status);
        $this->assertSame('Pelanggan membatalkan', $order->cancel_reason);
        $this->assertSame(10, $variant->fresh()->stock);
        $this->assertStringContainsString('Pelanggan membatalkan', $order->histories()->get()->last()->note);

        // Kirim ulang (halaman basi) atau batalkan yang sudah batal: ditolak, stok tidak bertambah lagi.
        $this->post("/admin/orders/{$order->id}/cancel", $payload)->assertSessionHasErrors('status');
        $this->post("/admin/orders/{$order->id}/cancel", ['current_status' => 'cancelled', 'reason' => 'Coba lagi'])
            ->assertSessionHasErrors('status');

        $this->assertSame(10, $variant->fresh()->stock);
    }

    public function test_pembatalan_wajib_alasan(): void
    {
        $variant = $this->variant(10);
        $order = $this->placeOrder($variant, 3);
        $this->actingAs($this->admin());

        foreach (['', 'abc'] as $reason) {
            $this->post("/admin/orders/{$order->id}/cancel", ['current_status' => 'pending', 'reason' => $reason])
                ->assertSessionHasErrors('reason');
        }

        $this->assertSame(OrderStatus::Pending, $order->fresh()->status);
        $this->assertSame(7, $variant->fresh()->stock);
    }

    public function test_pesanan_selesai_tidak_bisa_dibatalkan(): void
    {
        $variant = $this->variant(10);
        $order = $this->placeOrder($variant, 2);
        $order->update(['status' => 'completed']);
        $this->actingAs($this->admin());

        $this->post("/admin/orders/{$order->id}/cancel", ['current_status' => 'completed', 'reason' => 'Salah klik'])
            ->assertSessionHasErrors('status');

        $this->assertSame(OrderStatus::Completed, $order->fresh()->status);
        $this->assertSame(8, $variant->fresh()->stock);
    }

    public function test_pembatalan_tetap_berhasil_jika_produk_sudah_dihapus(): void
    {
        $variant = $this->variant(10);
        $order = $this->placeOrder($variant, 2);
        $variant->product->delete();
        $this->actingAs($this->admin());

        $this->post("/admin/orders/{$order->id}/cancel", ['current_status' => 'pending', 'reason' => 'Produk sudah dihapus'])
            ->assertSessionHasNoErrors();

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertDatabaseHas('order_items', ['order_id' => $order->id, 'product_variant_id' => null]);
        $this->assertStringContainsString('tidak dikembalikan', $order->histories()->get()->last()->note);
    }

    public function test_catatan_pengantaran_tersimpan_untuk_pesanan_antar(): void
    {
        $order = $this->placeOrder($this->variant(), 1, $this->deliveryPayload());
        $this->actingAs($this->admin());

        $this->patch("/admin/orders/{$order->id}/delivery-notes", ['delivery_notes' => 'Diantar Andi, 20.00'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Diantar Andi, 20.00', $order->fresh()->delivery_notes);
    }

    public function test_catatan_pengantaran_ditolak_untuk_pickup(): void
    {
        $order = $this->placeOrder($this->variant(), 1);
        $this->actingAs($this->admin());

        $this->patch("/admin/orders/{$order->id}/delivery-notes", ['delivery_notes' => 'Tes'])
            ->assertSessionHasErrors('delivery_notes');

        $this->assertNull($order->fresh()->delivery_notes);
    }
}
