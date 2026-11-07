<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'password-uji-12345';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function admin(): User
    {
        return User::factory()->create(['password' => self::PASSWORD]);
    }

    public function test_tamu_diarahkan_ke_halaman_login(): void
    {
        $this->get('/admin')->assertRedirect(route('admin.login'));
    }

    public function test_halaman_login_dapat_dibuka(): void
    {
        $this->get('/admin/login')->assertOk()->assertSee('Masuk');
    }

    public function test_admin_dapat_login_dan_membuka_dashboard(): void
    {
        $user = $this->admin();

        $this->post('/admin/login', ['email' => $user->email, 'password' => self::PASSWORD])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->get('/admin')->assertOk()->assertSee('Dashboard');
    }

    public function test_password_salah_ditolak(): void
    {
        $user = $this->admin();

        $this->post('/admin/login', ['email' => $user->email, 'password' => 'salah'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_login_dibatasi_setelah_lima_percobaan_gagal(): void
    {
        $user = $this->admin();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/admin/login', ['email' => $user->email, 'password' => 'salah']);
        }

        // Percobaan ke-6 dengan password BENAR tetap harus diblokir.
        $this->post('/admin/login', ['email' => $user->email, 'password' => self::PASSWORD])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_admin_dapat_logout(): void
    {
        $this->actingAs($this->admin())
            ->post('/admin/logout')
            ->assertRedirect(route('admin.login'));

        $this->assertGuest();
    }

    public function test_tidak_ada_halaman_registrasi(): void
    {
        $this->get('/register')->assertNotFound();
    }

    public function test_omzet_dashboard_hanya_dari_pesanan_selesai(): void
    {
        $base = [
            'customer_name' => 'Tes',
            'whatsapp' => '081234567890',
            'fulfillment_type' => 'pickup',
            'payment_method' => 'cash',
            'shipping_fee' => 0,
        ];

        $selesai = Order::create($base + [
            'order_number' => 'VNR-TES-0001', 'subtotal' => 75000, 'total' => 75000, 'status' => 'completed',
        ]);
        OrderStatusHistory::create(['order_id' => $selesai->id, 'status' => 'completed']);

        Order::create($base + [
            'order_number' => 'VNR-TES-0002', 'subtotal' => 40000, 'total' => 40000, 'status' => 'cancelled',
        ]);

        $this->actingAs($this->admin())
            ->get('/admin')
            ->assertOk()
            ->assertViewHas('stats', fn (array $s) => $s['revenue'] === 75000
                && $s['completed'] === 1
                && $s['orders'] === 2);
    }
}
