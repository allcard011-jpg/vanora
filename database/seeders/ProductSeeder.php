<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProductSeeder extends Seeder
{
    /** Ukuran => harga awal (rupiah). Bisa diubah admin; ini hanya nilai awal. */
    private const SIZES = [2 => 20000, 5 => 40000, 10 => 75000];

    /** Stok awal placeholder per ukuran. Sesuaikan lewat dashboard. */
    private const INITIAL_STOCK = 10;

    public function run(): void
    {
        $brand = Brand::where('slug', 'mykonos')->firstOrFail();
        $categories = Category::pluck('id', 'slug');

        foreach ($this->products() as $data) {
            $product = Product::firstOrCreate(
                ['slug' => Str::slug('mykonos '.$data['name'])],
                [
                    'brand_id' => $brand->id,
                    'category_id' => $categories[$data['category']],
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'top_notes' => $data['top'],
                    'middle_notes' => $data['middle'],
                    'base_notes' => $data['base'],
                    'is_active' => true,
                    'is_featured' => $data['featured'] ?? false,
                ]
            );

            foreach (self::SIZES as $size => $price) {
                $product->variants()->firstOrCreate(
                    ['size_ml' => $size],
                    ['price' => $price, 'stock' => self::INITIAL_STOCK, 'is_active' => true]
                );
            }
        }
    }

    private function products(): array
    {
        return [
            [
                'name' => 'Vanilla Clouds', 'category' => 'wanita', 'featured' => true,
                'description' => 'Aroma vanila lembut yang manis dan bersih dengan sentuhan marshmallow dan karamel.',
                'top' => 'Vanilla, Heliotrope, White Floral Accord',
                'middle' => 'Vanilla, Marshmallow, Iris',
                'base' => 'Caramel, White Musk, Vanilla',
            ],
            [
                'name' => 'Pink Beach', 'category' => 'wanita',
                'description' => 'Perpaduan buah segar dan bunga yang ceria dengan dasar powdery yang lembut.',
                'top' => 'Kiwi, Rhubarb, Pink Pepper',
                'middle' => 'Jasmine, Watermelon, Cyclamen',
                'base' => 'Powdery Notes, Sandalwood, Tonka Bean, Cedar, Musk, Juniper Berries',
            ],
            [
                'name' => 'Sparkling Rose', 'category' => 'wanita',
                'description' => 'Mawar yang segar dan berkilau dengan sentuhan buah beri merah dan dasar yang hangat.',
                'top' => 'Damask Rose, Orange, Geranium',
                'middle' => 'May Rose, Sparkling Red Berries, Plum, Sandalwood',
                'base' => 'Patchouli, Amber, Guaiac Wood, Musk',
            ],
            [
                'name' => 'Pink Drops', 'category' => 'wanita',
                'description' => 'Aroma stroberi manis dan susu yang lembut, ringan untuk dipakai sehari-hari.',
                'top' => 'Strawberry Preserve, Almond, Caramel',
                'middle' => 'Milk, Heliotrope',
                'base' => 'Vanilla, White Musk',
            ],
            [
                'name' => 'Cafe Drops', 'category' => 'unisex', 'featured' => true,
                'description' => 'Kopi hangat berpadu vanila, karamel, dan susu dengan nuansa manis yang nyaman.',
                'top' => 'Orchid, Jasmine, Coffee',
                'middle' => 'Vanilla, Caramel, Tonka Bean, Milk',
                'base' => 'Amber, Musk, Wood, Patchouli',
            ],
            [
                'name' => 'Senja', 'category' => 'unisex',
                'description' => 'Aroma gourmand hangat dari kopi, almond, dan cokelat dengan dasar kayu.',
                'top' => 'Coffee, Almond',
                'middle' => 'Patchouli, Chocolate, Milk',
                'base' => 'Sandalwood, Chocolate, Vetiver, Cistus',
            ],
            [
                'name' => 'Bonfire Vanilla', 'category' => 'unisex',
                'description' => 'Vanila hangat dengan rempah dan kesan kayu berasap untuk suasana santai.',
                'top' => 'Cloves, Nutmeg, Pink Pepper, Orange Zest, Vanilla',
                'middle' => 'Smoked Wood, Pepper, Guaiac Wood, Burnt Brown Sugar',
                'base' => 'Smoked Wood, Vanilla, Marshmallow, Caramel',
            ],
            [
                'name' => 'Aphrodite', 'category' => 'unisex',
                'description' => 'Karakter hangat dan berani dari melati, kayu manis, dan tembakau dengan dasar vanila.',
                'top' => 'Jasmine, Burnt Cinnamon, Myrrh',
                'middle' => 'Cedarwood, Tonka Bean, Sandalwood',
                'base' => 'White Musk, Tobacco, Vanilla, Mineral Amber',
            ],
            [
                'name' => 'Reflection', 'category' => 'unisex',
                'description' => 'Segar dan bersih dengan jeruk, nuansa laut, dan dasar musk-amber.',
                'top' => 'Grapefruit, Ginger, Bergamot',
                'middle' => 'Marine Notes, Cardamom, Lily of the Valley',
                'base' => 'Musk, Patchouli, Amber, Ambroxan',
            ],
            [
                'name' => 'Down to Earth', 'category' => 'pria',
                'description' => 'Segar sekaligus hangat dari teh matcha, dupa, dan kayu-kayuan yang tenang.',
                'top' => 'Incense, Matcha Tea, Cinnamon, Bergamot, Fig',
                'middle' => 'Orcanox, Cashmere Wood, Orange',
                'base' => 'Vetiver, Cedar, Sandalwood',
            ],
            [
                'name' => 'California Signature', 'category' => 'pria', 'featured' => true,
                'description' => 'Kesan segar khas pesisir dari jeruk, lavender, dan nuansa akuatik dengan dasar kayu.',
                'top' => 'Mandarin Orange, Lemon',
                'middle' => 'Lavender, Aquatic Notes, Lychee, Rosemary, Cardamom, Geranium',
                'base' => 'Teak Wood, Tonka Bean, Vetiver',
            ],
            [
                'name' => 'California Club', 'category' => 'pria',
                'description' => 'Bergamot dan lemon yang segar berpadu bunga violet dan iris dengan dasar kayu.',
                'top' => 'Bergamot, Lemon, Violet Leaf',
                'middle' => 'Rose, Violet, Iris',
                'base' => 'Amberwood, Ambrette, Sandalwood',
            ],
            [
                'name' => 'Conquer', 'category' => 'pria', 'featured' => true,
                'description' => 'Maskulin dan hangat, dibuka dengan bergamot dan jahe lalu berakhir ke patchouli dan amber.',
                'top' => 'Bergamot, Ginger, Cardamom',
                'middle' => 'Orris, Orange Blossom',
                'base' => 'Patchouli, Musk, Modern Amber, Vanilla',
            ],
        ];
    }
}
