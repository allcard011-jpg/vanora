<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductVariantController extends Controller
{
    public function store(Request $request, Product $product): RedirectResponse
    {
        $data = $request->validateWithBag('newVariant', [
            'size_ml' => [
                'required', 'integer', 'between:1,1000',
                Rule::unique('product_variants', 'size_ml')->where('product_id', $product->id),
            ],
            'price' => ['required', 'integer', 'between:1,10000000'],
            'stock' => ['required', 'integer', 'between:0,100000'],
        ]);

        $product->variants()->create($data + ['is_active' => $request->boolean('is_active')]);

        return redirect()->route('admin.products.edit', $product)
            ->with('status', "Ukuran {$data['size_ml']} ml ditambahkan.");
    }

    public function update(Request $request, ProductVariant $variant): RedirectResponse
    {
        $data = $request->validateWithBag('variant'.$variant->id, [
            'price' => ['required', 'integer', 'between:1,10000000'],
            'stock' => ['required', 'integer', 'between:0,100000'],
        ]);

        $variant->update($data + ['is_active' => $request->boolean('is_active')]);

        return redirect()->route('admin.products.edit', $variant->product_id)
            ->with('status', "Ukuran {$variant->size_ml} ml diperbarui.");
    }

    public function destroy(ProductVariant $variant): RedirectResponse
    {
        $productId = $variant->product_id;
        $size = $variant->size_ml;
        $variant->delete();

        return redirect()->route('admin.products.edit', $productId)
            ->with('status', "Ukuran {$size} ml dihapus.");
    }
}
