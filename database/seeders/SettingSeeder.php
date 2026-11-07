<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'store_name' => 'Vanora Parfum',
            'store_address' => 'Minggu Raya, Seberang Kopi LodBrok',
            'store_whatsapp' => '6285124048406', // format internasional untuk tautan wa.me
            'opening_hours' => 'Selasa–Minggu, 19.00–22.00 WITA',
            'delivery_area' => 'Banjarbaru',
            'shipping_fee' => '10000',
            'payment_info_cash' => 'Bayar tunai saat pesanan diambil di toko atau diterima di alamat.',
            'payment_info_qris' => 'Bayar dengan QRIS saat pesanan diambil atau diterima. Scan QRIS toko dan masukkan sesuai total pesanan.',
            'qris_image_path' => 'images/qris.png',
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
