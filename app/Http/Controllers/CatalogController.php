<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public const SORTS = [
        'terbaru' => 'Terbaru',
        'harga-terendah' => 'Harga Terendah',
        'harga-tertinggi' => 'Harga Tertinggi',
        'nama' => 'Nama A–Z',
    ];

    public function index(Request $request): View
    {
        $q = Str::limit(trim((string) $request->query('q')), 100, '');
        $category = (string) $request->query('kategori');
        $brand = (string) $request->query('merek');
        $sort = (string) $request->query('urut', 'terbaru');

        if (! array_key_exists($sort, self::SORTS)) {
            $sort = 'terbaru';
        }

        $query = Product::available()->withStartingPrice()->with(['brand', 'category']);

        if ($q !== '') {
            // %, _ dan \ dianggap karakter biasa, bukan wildcard.
            $like = '%'.addcslashes($q, '%_\\').'%';
            $query->where(fn ($w) => $w
                ->where('name', 'ilike', $like)
                ->orWhereHas('brand', fn ($b) => $b->where('name', 'ilike', $like)));
        }

        if ($category !== '') {
            $query->whereHas('category', fn ($c) => $c->where('slug', $category));
        }

        if ($brand !== '') {
            $query->whereHas('brand', fn ($b) => $b->where('slug', $brand));
        }

        match ($sort) {
            'harga-terendah' => $query->orderBy('min_price')->orderBy('id'),
            'harga-tertinggi' => $query->orderByDesc('min_price')->orderBy('id'),
            'nama' => $query->orderBy('name')->orderBy('id'),
            default => $query->orderByDesc('created_at')->orderByDesc('id'),
        };

        return view('shop.catalog', [
            'products' => $query->paginate(12)->withQueryString(),
            'categories' => Category::where('is_active', true)->orderBy('id')->get(),
            'brands' => Brand::whereHas('products', fn ($p) => $p->available())->orderBy('name')->get(),
            'filters' => ['q' => $q, 'kategori' => $category, 'merek' => $brand, 'urut' => $sort],
            'sorts' => self::SORTS,
        ]);
    }

    public function show(string $slug): View
    {
        $product = Product::available()->with(['brand', 'category'])
            ->where('slug', $slug)
            ->firstOrFail();

        $variants = $product->variants()->where('is_active', true)->orderBy('size_ml')->get();

        $related = Product::available()->withStartingPrice()->with(['brand', 'category'])
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->inRandomOrder()
            ->limit(4)
            ->get();

        return view('shop.product', compact('product', 'variants', 'related'));
    }
}
