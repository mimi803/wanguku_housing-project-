<?php

namespace App\Http\Controllers;

use App\Models\FarmDailyRecord;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FarmDailyRecordController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $records = FarmDailyRecord::with('farmer')
            ->when($user->role !== 'admin', fn ($query) => $query->where('farmer_id', $user->id))
            ->latest('recorded_on')
            ->limit(60)
            ->get();

        $latest = $records->first();
        $week = $records->filter(fn ($record) => $record->recorded_on->gte(now()->subDays(6)->startOfDay()));

        return view('dashboard.daily-records', [
            'records' => $records,
            'latest' => $latest,
            'weekEggs' => $week->sum('eggs_collected'),
            'weekFeed' => $week->sum('feed_kg'),
            'weekDeaths' => $week->sum('deaths'),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'recorded_on' => ['required', 'date', 'before_or_equal:today'],
            'flock_headcount' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'eggs_collected' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'feed_kg' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'water_litres' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'deaths' => ['required', 'integer', 'min:0', 'max:100000'],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);

        FarmDailyRecord::updateOrCreate(
            ['farmer_id' => $user->id, 'recorded_on' => $data['recorded_on']],
            collect($data)->except('recorded_on')->all(),
        );

        return back()->with('status', 'Daily flock and production record saved.');
    }
}
