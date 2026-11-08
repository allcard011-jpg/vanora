<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    /**
     * Membuat pesanan beserta item. Riwayat "completed" hanya dibuat untuk pesanan selesai.
     *
     * @param  array<int, array{name: string, price: int, qty: int, size?: int}>  $items
     */
    private function order(string $completedAt, array $items, int $shipping = 0, string $status = 'completed'): Order
    {
        $subtotal = (int) collect($items)->sum(fn ($item) => $item['price'] * $item['qty']);

        $order = Order::create([
            'order_number' => 'VNR-TES-'.Str::upper(Str::random(8)),
            'customer_name' => 'Tes',
            'whatsapp' => '6281234567890',
            'fulfillment_type' => $shipping > 0 ? 'delivery' : 'pickup',
            'payment_method' => 'cash',
            'delivery_address' => $shipping > 0 ? 'Jl. Tes No. 1, Banjarbaru' : null,
            'subtotal' => $subtotal,
            'shipping_fee' => $shipping,
            'total' => $subtotal + $shipping,
            'status' => $status,
        ]);

        foreach ($items as $item) {
            $order->items()->create([
                'brand_name' => 'Mykonos',
                'product_name' => $item['name'],
                'size_ml' => $item['size'] ?? 5,
                'unit_price' => $item['price'],
                'quantity' => $item['qty'],
                'line_total' => $item['price'] * $item['qty'],
            ]);
        }

        if ($status === 'completed') {
            $this->completedAt($order, $completedAt);
        }

        return $order;
    }

    private function completedAt(Order $order, string $at): void
    {
        (new OrderStatusHistory)->forceFill([
            'order_id' => $order->id,
            'status' => 'completed',
            'created_at' => $at,
        ])->save();
    }

    private function getReport(string $query = ''): TestResponse
    {
        return $this->actingAs(User::factory()->create())->get('/admin/reports'.$query);
    }

    public function test_tamu_tidak_dapat_membuka_laporan(): void
    {
        $this->get('/admin/reports')->assertRedirect(route('admin.login'));
    }

    public function test_laporan_terbuka_dengan_periode_bawaan(): void
    {
        $this->getReport()->assertOk()->assertSee('Laporan Penjualan');
    }

    public function test_laporan_harian_hanya_menghitung_pesanan_selesai_pada_periode(): void
    {
        $vanilla = ['name' => 'Vanilla Clouds', 'price' => 40000, 'qty' => 1];

        $this->order('2026-11-03 10:00:00', [['name' => 'Vanilla Clouds', 'price' => 40000, 'qty' => 2]]); // 80.000
        $this->order('2026-11-03 21:30:00', [
            ['name' => 'Cafe Drops', 'price' => 75000, 'qty' => 1, 'size' => 10],
            ['name' => 'Vanilla Clouds', 'price' => 40000, 'qty' => 1],
        ], shipping: 10000); // 115.000 + 10.000 = 125.000
        $this->order('2026-11-04 09:00:00', [['name' => 'Conquer', 'price' => 20000, 'qty' => 3, 'size' => 2]]); // 60.000

        // Tidak boleh dihitung:
        $this->order('2026-10-31 20:00:00', [$vanilla]);                          // di luar periode
        $this->order('2026-11-03 12:00:00', [$vanilla], status: 'cancelled');     // dibatalkan
        $this->order('2026-11-03 12:00:00', [$vanilla], status: 'pending');       // belum selesai

        $this->getReport('?from=2026-11-01&to=2026-11-30&group=day')
            ->assertOk()
            ->assertSee('Rp265.000')
            ->assertViewHas('report', function (array $r) {
                return $r['totals']['orders'] === 3
                    && $r['totals']['units'] === 7
                    && $r['totals']['subtotal'] === 255000
                    && $r['totals']['shipping'] === 10000
                    && $r['totals']['revenue'] === 265000
                    && $r['totals']['average'] === 88333
                    && count($r['rows']) === 2
                    && $r['rows'][0]['period'] === '2026-11-03'
                    && $r['rows'][0]['orders'] === 2
                    && $r['rows'][0]['units'] === 4
                    && $r['rows'][0]['revenue'] === 205000
                    && $r['rows'][1]['period'] === '2026-11-04'
                    && $r['rows'][1]['revenue'] === 60000;
            });
    }

    public function test_laporan_bulanan_mengelompokkan_per_bulan(): void
    {
        $this->order('2026-10-15 10:00:00', [['name' => 'Vanilla Clouds', 'price' => 40000, 'qty' => 1]]);
        $this->order('2026-11-02 10:00:00', [['name' => 'Vanilla Clouds', 'price' => 40000, 'qty' => 2]]);
        $this->order('2026-11-20 10:00:00', [['name' => 'Cafe Drops', 'price' => 75000, 'qty' => 1, 'size' => 10]]);

        $this->getReport('?from=2026-10-01&to=2026-11-30&group=month')
            ->assertOk()
            ->assertViewHas('report', function (array $r) {
                return count($r['rows']) === 2
                    && $r['rows'][0]['period'] === '2026-10'
                    && $r['rows'][0]['orders'] === 1
                    && $r['rows'][0]['revenue'] === 40000
                    && $r['rows'][1]['period'] === '2026-11'
                    && $r['rows'][1]['orders'] === 2
                    && $r['rows'][1]['units'] === 3
                    && $r['rows'][1]['revenue'] === 155000;
            });
    }

    public function test_batas_hari_dihitung_menurut_waktu_lokal(): void
    {
        $item = [['name' => 'Vanilla Clouds', 'price' => 40000, 'qty' => 1]];

        $this->order('2026-11-02 23:59:59', $item);
        $this->order('2026-11-03 23:59:59', $item);
        $this->order('2026-11-04 00:00:00', $item);

        $this->getReport('?from=2026-11-03&to=2026-11-03&group=day')
            ->assertViewHas('report', fn (array $r) => $r['totals']['orders'] === 1
                && $r['rows'][0]['period'] === '2026-11-03');
    }

    public function test_produk_terlaris_diurutkan_berdasarkan_jumlah_terjual(): void
    {
        $this->order('2026-11-03 10:00:00', [
            ['name' => 'Vanilla Clouds', 'price' => 40000, 'qty' => 2],
            ['name' => 'Cafe Drops', 'price' => 75000, 'qty' => 1, 'size' => 10],
        ]);
        $this->order('2026-11-03 11:00:00', [['name' => 'Vanilla Clouds', 'price' => 40000, 'qty' => 1]]);

        $this->getReport('?from=2026-11-01&to=2026-11-30')
            ->assertViewHas('report', fn (array $r) => count($r['top']) === 2
                && $r['top'][0]['product_name'] === 'Vanilla Clouds'
                && $r['top'][0]['units'] === 3
                && $r['top'][0]['revenue'] === 120000
                && $r['top'][1]['product_name'] === 'Cafe Drops'
                && $r['top'][1]['units'] === 1);
    }

    public function test_pesanan_dihitung_sekali_walau_riwayat_selesai_ganda(): void
    {
        $order = $this->order('2026-11-03 10:00:00', [['name' => 'Vanilla Clouds', 'price' => 40000, 'qty' => 1]]);
        $this->completedAt($order, '2026-11-05 10:00:00');

        $this->getReport('?from=2026-11-01&to=2026-11-30')
            ->assertViewHas('report', fn (array $r) => $r['totals']['orders'] === 1 && $r['totals']['revenue'] === 40000);

        // Yang dipakai adalah saat PERTAMA selesai (3 Nov), bukan 5 Nov.
        $this->getReport('?from=2026-11-05&to=2026-11-05')
            ->assertViewHas('report', fn (array $r) => $r['totals']['orders'] === 0 && $r['rows'] === []);
    }

    public function test_validasi_periode_tidak_valid(): void
    {
        $this->getReport('?from=2026-11-10&to=2026-11-01')
            ->assertOk()
            ->assertSee('harus sama dengan atau setelah');

        $this->getReport('?from=2020-01-01&to=2026-11-30')
            ->assertOk()
            ->assertSee('maksimal 366 hari');

        $this->getReport('?from=abc&to=2026-11-30')
            ->assertOk()
            ->assertSee('harus berformat');

        $this->getReport('?group=tahun')
            ->assertOk()
            ->assertSee('tidak valid');
    }

    public function test_zona_waktu_aplikasi_adalah_wita(): void
    {
        $this->assertSame(
            'Asia/Makassar',
            config('app.timezone'),
            'Laporan harian mengikuti zona waktu aplikasi. Set APP_TIMEZONE=Asia/Makassar di .env.'
        );
    }
}
