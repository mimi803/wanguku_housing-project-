<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Services\ResourceMapClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function create(Product $product)
    {
        abort_unless($product->stock > 0, 404);
        return view('orders.create', compact('product'));
    }

    public function store(Request $request, Product $product)
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:10000'],
            'phone' => ['required', 'string', 'max:20'],
            'delivery_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $order = DB::transaction(function () use ($request, $product, $data) {
            $lockedProduct = Product::query()->lockForUpdate()->findOrFail($product->id);
            abort_if($lockedProduct->farmer_id === $request->user()->id, 403, 'You cannot order your own listing.');
            abort_if($lockedProduct->stock < $data['quantity'], 422, 'There is not enough stock for that quantity.');

            $lockedProduct->decrement('stock', $data['quantity']);

            return Order::create([
                'product_id' => $lockedProduct->id,
                'buyer_id' => $request->user()->id,
                'quantity' => $data['quantity'],
                'total_amount' => (float) $lockedProduct->price * $data['quantity'],
                'phone' => $data['phone'],
                'delivery_notes' => $data['delivery_notes'] ?? null,
                'payment_method' => 'arrange_with_farmer',
                'payment_status' => 'unpaid',
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $order->load('product.farmer');
        if ($order->product?->farmer) {
            app(ResourceMapClient::class)->sync($order->product->farmer);
        }

        return redirect()->route('orders.mine')->with('status', 'Order request sent. Contact the farmer to agree on payment and collection or delivery.');
    }

    public function buyerIndex(Request $request)
    {
        $orders = Order::with('product.farmer')->where('buyer_id', $request->user()->id)->latest('created_at')->paginate(20);
        return view('orders.mine', compact('orders'));
    }

    public function farmerIndex(Request $request)
    {
        $user = $request->user();
        $orders = Order::with(['product.farmer', 'buyer'])
            ->when($user->role !== 'admin', fn ($query) => $query->whereHas('product', fn ($product) => $product->where('farmer_id', $user->id)))
            ->latest('created_at')->paginate(30);
        return view('dashboard.orders', compact('orders'));
    }

    public function updateStatus(Request $request, Order $order)
    {
        $user = $request->user();
        abort_unless($user->role === 'admin' || $order->product()->where('farmer_id', $user->id)->exists(), 403);

        $data = $request->validate([
            'status' => ['required', Rule::in(['confirmed', 'ready', 'completed', 'cancelled'])],
            'payment_status' => ['nullable', Rule::in(['unpaid', 'paid', 'refunded'])],
        ]);

        DB::transaction(function () use ($order, $data) {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            $allowed = match ($locked->status) {
                'pending' => ['confirmed', 'cancelled'],
                'confirmed' => ['ready', 'cancelled'],
                'ready' => ['completed', 'cancelled'],
                default => [],
            };
            abort_unless(in_array($data['status'], $allowed, true) || $data['status'] === $locked->status, 422, 'That order status change is not allowed.');

            if ($data['status'] === 'cancelled' && $locked->status !== 'cancelled') {
                Product::query()->whereKey($locked->product_id)->increment('stock', $locked->quantity);
            }

            $locked->status = $data['status'];
            if (isset($data['payment_status'])) $locked->payment_status = $data['payment_status'];
            $locked->updated_at = now();
            $locked->save();
        });

        $order->load('product.farmer');
        if ($order->product?->farmer) {
            app(ResourceMapClient::class)->sync($order->product->farmer);
        }

        return back()->with('status', 'Order updated.');
    }
}
