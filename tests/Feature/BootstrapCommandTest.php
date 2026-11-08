<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class BootstrapCommandTest extends TestCase
{
    use RefreshDatabase;

    private function adminConfig(string $password): void
    {
        config(['vanora.admin_email' => 'admin@vanora.test', 'vanora.admin_password' => $password]);
    }

    public function test_database_baru_diisi_dan_data_yang_dihapus_tidak_dihidupkan_kembali(): void
    {
        $this->adminConfig('password-uji-12345');

        $this->artisan('vanora:bootstrap')->assertSuccessful();

        $this->assertSame(13, Product::count());
        $this->assertDatabaseHas('users', ['email' => 'admin@vanora.test']);
        $this->assertNotNull(Setting::get('initial_seed_done'));

        // Admin mengubah data, lalu container menyala lagi (cold start).
        Product::where('slug', 'mykonos-conquer')->firstOrFail()->delete();
        Setting::put('shipping_fee', '12000');

        $this->artisan('vanora:bootstrap')->assertSuccessful();

        $this->assertSame(12, Product::count());
        $this->assertSame('12000', Setting::get('shipping_fee'));
    }

    public function test_password_admin_mengikuti_variabel_lingkungan(): void
    {
        $this->adminConfig('password-lama-12345');
        $this->artisan('vanora:bootstrap')->assertSuccessful();

        $this->adminConfig('password-baru-12345');
        $this->artisan('vanora:bootstrap')->assertSuccessful();

        $this->assertSame(1, User::count());
        $this->assertTrue(Hash::check('password-baru-12345', User::firstOrFail()->password));
    }

    public function test_gagal_jika_password_admin_kosong_dan_tidak_menandai_selesai(): void
    {
        $this->adminConfig('');

        try {
            Artisan::call('vanora:bootstrap');
            $this->fail('Seharusnya gagal karena ADMIN_PASSWORD kosong.');
        } catch (RuntimeException) {
            $this->assertNull(Setting::get('initial_seed_done'));
            $this->assertSame(0, Product::count());
        }
    }
}
