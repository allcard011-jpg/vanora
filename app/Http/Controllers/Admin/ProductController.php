<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Rules\ImagePath;
use App\Support\Slug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q'));

        $products = Product::query()
            ->with(['brand', 'category'])
            ->withMin('variants', 'price')
            ->withMax('variants', 'price')
            ->withSum('variants', 'stock')
            ->when($q !== '', fn ($query) => $query->where(function ($w) use ($q) {
                $w->where('name', 'ilike', "%{$q}%")
                    ->orWhereHas('brand', fn ($b) => $b->where('name', 'ilike', "%{$q}%"));
            }))
            ->when($request->filled('category'), fn ($query) => $query->where('category_id', (int) $request->query('category')))
            ->when($request->filled('brand'), fn ($query) => $query->where('brand_id', (int) $request->query('brand')))
            ->when($request->query('status') === 'active', fn ($query) => $query->where('is_active', true))
            ->when($request->query('status') === 'inactive', fn ($query) => $query->where('is_active', false))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'brands' => Brand::orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.products.form', $this->formData(new Product(['is_active' => true])));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $brand = Brand::findOrFail($data['brand_id']);

        $product = Product::create($data + [
            'slug' => Slug::unique($brand->name.' '.$data['name'], Product::class),
        ]);

        return redirect()->route('admin.products.edit', $product)
            ->with('status', 'Produk dibuat. Tambahkan ukuran, harga, dan stok di bawah.');
    }

    public function edit(Product $product): View
    {
        return view('admin.products.form', $this->formData($product));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $product->update($this->validated($request, $product));

        return redirect()->route('admin.products.edit', $product)->with('status', 'Produk diperbarui.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()->route('admin.products.index')->with('status', 'Produk dihapus.');
    }

    private function formData(Product $product): array
    {
        return [
            'product' => $product,
            'brands' => Brand::orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
            'variants' => $product->exists ? $product->variants()->orderBy('size_ml')->get() : collect(),
        ];
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:120',
                Rule::unique('products', 'name')->where('brand_id', $request->input('brand_id'))->ignore($product),
            ],
            'brand_id' => ['required', Rule::exists('brands', 'id')],
            'category_id' => ['required', Rule::exists('categories', 'id')],
            'description' => ['nullable', 'string', 'max:2000'],
            'top_notes' => ['nullable', 'string', 'max:255'],
            'middle_notes' => ['nullable', 'string', 'max:255'],
            'base_notes' => ['nullable', 'string', 'max:255'],
            'image_path' => ['nullable', 'string', 'max:500', new ImagePath],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['is_featured'] = $request->boolean('is_featured');

        return $data;
    }
}
