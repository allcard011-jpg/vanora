<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Pria' => 'pria', 'Wanita' => 'wanita', 'Unisex' => 'unisex'] as $name => $slug) {
            Category::firstOrCreate(['slug' => $slug], ['name' => $name, 'is_active' => true]);
        }
    }
}
