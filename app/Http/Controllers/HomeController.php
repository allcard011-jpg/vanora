<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $featured = Product::available()->withStartingPrice()->with(['brand', 'category'])
            ->where('is_featured', true)
            ->orderBy('name')
            ->limit(8)
            ->get();

        $newest = Product::available()->withStartingPrice()->with(['brand', 'category'])
            ->latest()->orderByDesc('id')
            ->limit(8)
            ->get();

        $categories = Category::where('is_active', true)
            ->withCount(['products as available_count' => fn ($p) => $p->available()])
            ->orderBy('id')
            ->get();

        return view('shop.home', compact('featured', 'newest', 'categories'));
    }
}
