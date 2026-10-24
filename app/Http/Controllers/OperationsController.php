<?php

namespace App\Http\Controllers;

use App\Models\FarmOperationLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OperationsController extends Controller
{
    private const TYPES = ['feed', 'water', 'health', 'labour', 'incident'];
    private const STATUSES = ['logged', 'attention', 'resolved'];

    public function index(Request $request)
    {
        $user = $request->user();
        $query = FarmOperationLog::with('farmer')->when($user->role !== 'admin', fn ($q) => $q->where('farmer_id', $user->id));
        $logs = (clone $query)->latest('recorded_at')->limit(100)->get();
        $summary = (clone $query)->where('recorded_at', '>=', now()->subDays(7))->selectRaw('operation_type, count(*) as total')->groupBy('operation_type')->pluck('total', 'operation_type');
        return view('dashboard.operations', ['logs' => $logs, 'summary' => $summary, 'types' => self::TYPES]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'operation_type' => ['required', Rule::in(self::TYPES)],
            'title' => ['required', 'string', 'max:160'],
            'recorded_at' => ['required', 'date'],
            'quantity' => ['nullable', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:32'],
            'status' => ['required', Rule::in(self::STATUSES)],
            'flock_batch' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);
        $data['farmer_id'] = $request->user()->id;
        FarmOperationLog::create($data);
        return back()->with('status', 'Farm record saved.');
    }
}
