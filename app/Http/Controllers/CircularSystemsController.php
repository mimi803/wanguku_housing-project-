<?php

namespace App\Http\Controllers;

use App\Models\CircularResourceLog;
use App\Models\FarmSystemPlan;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CircularSystemsController extends Controller
{
    private const SYSTEM_TYPES = ['poultry' => 'Poultry', 'vegetables' => 'Vegetables', 'aquaculture' => 'Aquaculture', 'water_treatment' => 'Water treatment', 'organic_waste' => 'Organic waste', 'biogas' => 'Biogas', 'shared_space' => 'Shared / people space', 'other' => 'Other'];
    private const RESOURCE_TYPES = ['manure' => 'Manure', 'organic_waste' => 'Other organic material', 'wastewater' => 'Wastewater', 'treated_water' => 'Treated water', 'biogas' => 'Biogas', 'fish' => 'Fish', 'vegetables' => 'Vegetables', 'other' => 'Other'];

    public function index(Request $request)
    {
        $user = $request->user();
        $plans = FarmSystemPlan::with('farmer')->when($user->role !== 'admin', fn ($query) => $query->where('farmer_id', $user->id))->orderBy('floor_number')->get();
        $flows = CircularResourceLog::with('farmer')->when($user->role !== 'admin', fn ($query) => $query->where('farmer_id', $user->id))->latest('recorded_on')->limit(50)->get();
        return view('dashboard.circular-systems', ['plans' => $plans, 'flows' => $flows, 'systemTypes' => self::SYSTEM_TYPES, 'resourceTypes' => self::RESOURCE_TYPES]);
    }

    public function storePlan(Request $request)
    {
        $data = $request->validate([
            'floor_number' => ['required', 'integer', 'between:1,7'],
            'system_type' => ['required', Rule::in(array_keys(self::SYSTEM_TYPES))],
            'name' => ['required', 'string', 'max:120'],
            'stage' => ['required', Rule::in(['concept', 'planned', 'pilot', 'active'])],
            'target_year' => ['required', 'integer', 'between:'.now()->year.',2035'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        FarmSystemPlan::create([...$data, 'farmer_id' => $request->user()->id]);
        return back()->with('status', 'Farm system plan saved.');
    }

    public function updatePlan(Request $request, FarmSystemPlan $plan)
    {
        abort_unless($request->user()->role === 'admin' || $plan->farmer_id === $request->user()->id, 403);
        $data = $request->validate(['stage' => ['required', Rule::in(['concept', 'planned', 'pilot', 'active'])]]);
        $plan->update($data);
        return back()->with('status', 'Plan stage updated.');
    }

    public function storeFlow(Request $request)
    {
        $data = $request->validate([
            'recorded_on' => ['required', 'date', 'before_or_equal:today'],
            'resource_type' => ['required', Rule::in(array_keys(self::RESOURCE_TYPES))],
            'source' => ['required', 'string', 'max:120'],
            'destination' => ['required', 'string', 'max:120'],
            'quantity' => ['nullable', 'numeric', 'min:0', 'max:100000000'],
            'unit' => ['nullable', 'string', 'max:24'],
            'review_status' => ['required', Rule::in(['not_for_reuse', 'review_required', 'specialist_reviewed'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        CircularResourceLog::create([...$data, 'farmer_id' => $request->user()->id, 'created_at' => now()]);
        return back()->with('status', 'Resource flow record saved. Review status is a farm-entered note, not a safety certification.');
    }
}
