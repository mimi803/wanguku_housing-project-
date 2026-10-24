<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ResourceMapClient;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::where('farmer_id', $request->user()->id)->latest('created_at')->get();

        return view('dashboard.products', [
            'products' => $products,
            'categories' => Product::CATEGORIES,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'category' => ['required', Rule::in(array_keys(Product::CATEGORIES))],
            'price' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'stock' => ['required', 'integer', 'min:0', 'max:100000'],
            'description' => ['nullable', 'string', 'max:3000'],
            'image_url' => ['nullable', 'url', 'max:255'],
            'image' => ['nullable', 'image', 'max:5120'],
        ]);
        unset($data['image']);
        if ($request->hasFile('image')) {
            $data['image_url'] = $request->file('image')->store('products', 'public');
        }
        $data['farmer_id'] = $request->user()->id;
        Product::create($data);

        $farmer = $request->user();
        $synced = ! $farmer->show_on_map || app(ResourceMapClient::class)->sync($farmer);

        $response = back()->with('status', 'Your listing is now visible to customers.');
        if ($farmer->show_on_map && ! $synced) {
            $response->with('integration_warning', 'Your Wanguku listing is saved, but the Resource Map could not be updated. Check the map connection settings.');
        }

        return $response;
    }
}
