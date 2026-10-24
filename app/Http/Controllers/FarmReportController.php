<?php

namespace App\Http\Controllers;

use App\Models\FarmDailyRecord;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;

class FarmReportController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'from' => ['nullable', 'date', 'before_or_equal:today'],
            'to' => ['nullable', 'date', 'before_or_equal:today'],
        ]);
        if (! empty($data['from']) && ! empty($data['to']) && $data['to'] < $data['from']) {
            return back()->withErrors(['to' => 'The end date must be on or after the start date.'])->withInput();
        }
        $from = $data['from'] ?? now()->subDays(29)->toDateString();
        $to = $data['to'] ?? now()->toDateString();
        $user = $request->user();

        $records = FarmDailyRecord::query()
            ->when($user->role !== 'admin', fn ($query) => $query->where('farmer_id', $user->id))
            ->whereBetween('recorded_on', [$from, $to])->orderBy('recorded_on')->get();
        $products = Product::query()
            ->when($user->role !== 'admin', fn ($query) => $query->where('farmer_id', $user->id))
            ->get(['id', 'title', 'stock', 'price']);
        $orders = Order::query()->whereBetween('created_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->when($user->role !== 'admin', fn ($query) => $query->whereHas('product', fn ($product) => $product->where('farmer_id', $user->id)))
            ->get();
        $completedPaid = $orders->where('status', 'completed')->where('payment_status', 'paid');

        return view('dashboard.reports', [
            'from' => $from,
            'to' => $to,
            'records' => $records,
            'products' => $products->sortBy('title'),
            'orderCount' => $orders->count(),
            'openOrders' => $orders->whereIn('status', ['pending', 'confirmed', 'ready'])->count(),
            'paidSales' => $completedPaid->sum('total_amount'),
            'paidOrderCount' => $completedPaid->count(),
            'totals' => [
                'eggs' => $records->sum('eggs_collected'),
                'feed' => $records->sum('feed_kg'),
                'water' => $records->sum('water_litres'),
                'deaths' => $records->sum('deaths'),
            ],
            'latestHeadcount' => $records->last()?->flock_headcount,
            'listedUnits' => $products->sum('stock'),
        ]);
    }
}
