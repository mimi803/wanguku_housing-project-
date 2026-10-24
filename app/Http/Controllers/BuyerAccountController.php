<?php

namespace App\Http\Controllers;

use App\Models\Product;

class BuyerAccountController extends Controller
{
    public function __invoke()
    {
        return view('account.index', [
            'products' => Product::with('farmer')->where('stock', '>', 0)->latest('created_at')->limit(8)->get(),
            'categories' => Product::CATEGORIES,
        ]);
    }
}
