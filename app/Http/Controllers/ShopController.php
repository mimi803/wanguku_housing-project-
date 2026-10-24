<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ShopController extends Controller
{
    public function __invoke(Request $request)
    {
        $filters = $request->validate([
            'category' => ['nullable', Rule::in(array_keys(Product::CATEGORIES))],
            'q' => ['nullable', 'string', 'max:100'],
            'farmer' => ['nullable', 'integer', Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'farmer'))],
        ]);
        $farmer = isset($filters['farmer']) ? User::query()->where('role', 'farmer')->findOrFail($filters['farmer']) : null;
        $products = Product::with('farmer')->where('stock', '>', 0)
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('category', $category))
            ->when($filters['farmer'] ?? null, fn ($query, $farmerId) => $query->where('farmer_id', $farmerId))
            ->when($filters['q'] ?? null, fn ($query, $term) => $query->where('title', 'like', '%'.$term.'%'))
            ->latest('created_at')->paginate(12)->withQueryString();

        return view('shop.index', ['products' => $products, 'categories' => Product::CATEGORIES, 'farmer' => $farmer]);
    }
}
