<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __invoke(Request $request)
    {
        $categories = [
            'kienyeji_eggs' => 'Farm eggs', 'layer_eggs' => 'Layer eggs',
            'hatching_eggs' => 'Hatching eggs', 'poultry_birds' => 'Chicken', 'chicks' => 'Day-old chicks',
            'fish' => 'Farmed fish', 'vegetables' => 'Fresh vegetables',
        ];
        $category = $request->query('cat');
        if (! array_key_exists($category, $categories)) $category = null;
        $products = Product::with('farmer')->where('stock', '>', 0)
            ->when($category, fn ($query) => $query->where('category', $category))
            ->latest('created_at')->limit(3)->get();
        return view('home', [
            'products' => $products,
            'categories' => $categories,
            'selectedCategory' => $category,
            'farmers' => User::where('role', 'farmer')->whereNotNull('phone')->latest('id')->limit(6)->get(),
            'articles' => Article::latest('created_at')->limit(3)->get(),
        ]);
    }
}
