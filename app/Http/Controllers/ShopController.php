<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShopController extends Controller
{
    public function __invoke(Request $request)
    {
        $filters = $request->validate([
            'category' => ['nullable', Rule::in(array_keys(Product::CATEGORIES))],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $products = Product::with('farmer')->where('stock', '>', 0)
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('category', $category))
            ->when($filters['q'] ?? null, fn ($query, $term) => $query->where('title', 'like', '%'.$term.'%'))
            ->latest('created_at')->paginate(12)->withQueryString();

        return view('shop.index', ['products' => $products, 'categories' => Product::CATEGORIES]);
    }
}
