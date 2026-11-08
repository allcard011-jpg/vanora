<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_header_keamanan_dasar_ada_di_halaman_publik(): void
    {
        $this->get('/katalog')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_csp_aktif_saat_debug_mati_dan_tidak_mengizinkan_skrip_inline(): void
    {
        if (is_file(public_path('hot'))) {
            $this->markTestSkipped('Server pengembangan Vite aktif (public/hot ada). Hentikan npm run dev.');
        }

        config(['app.debug' => false]);

        $csp = $this->get('/katalog')->headers->get('Content-Security-Policy');

        $this->assertNotNull($csp);
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("script-src 'self';", $csp);
        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringNotContainsString('unsafe-eval', $csp);
    }

    public function test_halaman_privat_tidak_disimpan_cache_tetapi_katalog_boleh(): void
    {
        $this->actingAs(User::factory()->create());

        $this->assertStringContainsString('no-store', $this->get('/admin')->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $this->get('/keranjang')->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', $this->get('/cek-pesanan')->headers->get('Cache-Control'));
        $this->assertStringNotContainsString('no-store', $this->get('/katalog')->headers->get('Cache-Control'));
    }

    public function test_hsts_hanya_dikirim_untuk_https_di_production(): void
    {
        $this->app['env'] = 'production';

        $this->get('https://localhost/katalog')->assertHeader('Strict-Transport-Security');

        $this->assertFalse($this->get('http://localhost/katalog')->headers->has('Strict-Transport-Security'));
    }

    public function test_formulir_tanpa_token_csrf_ditolak(): void
    {
        // Selama test, Laravel mematikan pemeriksaan CSRF; kita pura-pura bukan lingkungan testing.
        $this->app['env'] = 'local';

        $this->post('/keranjang', ['variant_id' => 1, 'quantity' => 1])
            ->assertStatus(419)
            ->assertSee('Sesi Anda telah berakhir');

        $this->post('/checkout', [])->assertStatus(419);
        $this->post('/admin/login', ['email' => 'a@example.com', 'password' => 'x'])->assertStatus(419);
    }

    public function test_halaman_tidak_ditemukan_memakai_tampilan_vanora(): void
    {
        $this->get('/halaman-yang-tidak-ada')
            ->assertNotFound()
            ->assertSee('Halaman tidak ditemukan');
    }

    public function test_pencarian_dengan_karakter_sql_diperlakukan_sebagai_teks_biasa(): void
    {
        $product = Product::create([
            'brand_id' => Brand::create(['name' => 'Mykonos', 'slug' => 'mykonos'])->id,
            'category_id' => Category::create(['name' => 'Unisex', 'slug' => 'unisex'])->id,
            'name' => 'Parfum Rahasia',
            'slug' => 'mykonos-parfum-rahasia',
        ]);
        $product->variants()->create(['size_ml' => 5, 'price' => 40000, 'stock' => 5]);

        foreach (["' OR '1'='1", "'; DROP TABLE products; --", '" OR 1=1 --'] as $payload) {
            $query = http_build_query(['q' => $payload, 'kategori' => $payload, 'merek' => $payload, 'urut' => $payload]);

            $this->get('/katalog?'.$query)
                ->assertOk()
                ->assertDontSee('Parfum Rahasia');
        }

        $this->assertDatabaseCount('products', 1);
    }

    public function test_login_tidak_membocorkan_apakah_email_terdaftar(): void
    {
        $user = User::factory()->create(['password' => 'password-uji-12345']);

        // Email terdaftar + password salah
        $this->post('/admin/login', ['email' => $user->email, 'password' => 'salah'])
            ->assertSessionHasErrors(['email' => 'Email atau password salah.']);

        // Email tidak terdaftar: pesannya harus persis sama
        $this->post('/admin/login', ['email' => 'tidak-ada@example.com', 'password' => 'salah'])
            ->assertSessionHasErrors(['email' => 'Email atau password salah.']);

        $this->assertGuest();
    }

    public function test_id_sesi_diganti_setelah_login(): void
    {
        $user = User::factory()->create(['password' => 'password-uji-12345']);

        $this->get('/admin/login');
        $sebelum = session()->getId();

        $this->post('/admin/login', ['email' => $user->email, 'password' => 'password-uji-12345'])
            ->assertRedirect();

        $this->assertNotSame($sebelum, session()->getId());
        $this->assertAuthenticatedAs($user);
    }
}
