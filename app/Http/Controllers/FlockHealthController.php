<?php

namespace App\Http\Controllers;

use App\Models\VaccinationSchedule;
use Illuminate\Http\Request;

class FlockHealthController extends Controller
{
    public function index(Request $request)
    {
        $schedules = VaccinationSchedule::with('farmer')
            ->when($request->user()->role !== 'admin', fn ($query) => $query->where('farmer_id', $request->user()->id))
            ->orderBy('completed')->orderBy('due_date')->paginate(30);
        return view('dashboard.flock-health', compact('schedules'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'batch_name' => ['required', 'string', 'max:100'],
            'vaccine_name' => ['required', 'string', 'max:150'],
            'due_date' => ['required', 'date'],
        ]);
        VaccinationSchedule::create([...$data, 'farmer_id' => $request->user()->id, 'completed' => false, 'created_at' => now()]);
        return back()->with('status', 'Health schedule saved. Follow a qualified animal-health professional’s guidance for the schedule and vaccine.');
    }

    public function complete(Request $request, VaccinationSchedule $schedule)
    {
        abort_unless($request->user()->role === 'admin' || $schedule->farmer_id === $request->user()->id, 403);
        $schedule->update(['completed' => true]);
        return back()->with('status', 'Schedule marked complete.');
    }
}
