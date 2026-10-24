<?php

namespace App\Http\Controllers;

use App\Models\FarmOperationLog;
use App\Models\FarmDailyRecord;
use App\Models\IncubationBatch;
use App\Models\Product;
use App\Models\VaccinationSchedule;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();
        $admin = $user->role === 'admin';
        $ownerScope = fn ($query) => $admin ? $query : $query->where('farmer_id', $user->id);
        $products = $ownerScope(Product::query());
        $incubations = $ownerScope(IncubationBatch::query());
        $vax = $ownerScope(VaccinationSchedule::query());
        $dailyRecords = $ownerScope(FarmDailyRecord::query());
        return view('dashboard.index', [
            'totalListings' => (clone $products)->count(),
            'totalStock' => (clone $products)->sum('stock'),
            'activeIncubations' => (clone $incubations)->where('completed', false)->count(),
            'totalDeaths' => (clone $dailyRecords)->sum('deaths'),
            'latestDailyRecord' => (clone $dailyRecords)->latest('recorded_on')->first(),
            'upcomingVax' => (clone $vax)->where('completed', false)->whereDate('due_date', '<=', now()->addDays(7))->orderBy('due_date')->limit(5)->get(),
            'recentLogs' => FarmOperationLog::with('farmer')->when(! $admin, fn ($q) => $q->where('farmer_id', $user->id))->latest('recorded_at')->limit(5)->get(),
        ]);
    }
}
