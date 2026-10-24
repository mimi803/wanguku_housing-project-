<?php

namespace App\Http\Controllers;

use App\Models\IncubationBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class HatcheryController extends Controller
{
    public function index(Request $request)
    {
        $batches = IncubationBatch::with('farmer')
            ->when($request->user()->role !== 'admin', fn ($query) => $query->where('farmer_id', $request->user()->id))
            ->orderByDesc('start_date')->paginate(30);
        return view('dashboard.hatchery', compact('batches'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'batch_name' => ['required', 'string', 'max:100'],
            'egg_count' => ['required', 'integer', 'min:1', 'max:100000'],
            'start_date' => ['required', 'date', 'before_or_equal:today'],
        ]);
        $start = Carbon::parse($data['start_date']);
        IncubationBatch::create([
            ...$data,
            'farmer_id' => $request->user()->id,
            'day7_candling' => $start->copy()->addDays(7)->toDateString(),
            'day14_candling' => $start->copy()->addDays(14)->toDateString(),
            'hatch_date' => $start->copy()->addDays(21)->toDateString(),
            'completed' => false,
            'created_at' => now(),
        ]);
        return back()->with('status', 'Incubation batch and candling dates saved.');
    }

    public function complete(Request $request, IncubationBatch $batch)
    {
        abort_unless($request->user()->role === 'admin' || $batch->farmer_id === $request->user()->id, 403);
        $data = $request->validate([
            'hatched_count' => ['required', 'integer', 'min:0', 'max:'.$batch->egg_count],
        ]);
        abort_if($batch->hatch_date->isFuture(), 422, 'Record hatch results on or after the expected hatch date.');
        $batch->update(['hatched_count' => $data['hatched_count'], 'completed' => true]);
        return back()->with('status', 'Hatch results saved.');
    }
}
