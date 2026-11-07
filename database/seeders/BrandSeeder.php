<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        Brand::firstOrCreate(['slug' => 'mykonos'], ['name' => 'Mykonos']);
    }
}
