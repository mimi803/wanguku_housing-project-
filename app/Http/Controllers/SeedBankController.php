<?php

namespace App\Http\Controllers;

use App\Models\SeedBankListing;
use App\Models\SeedBankRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SeedBankController extends Controller
{
    private const UNITS = ['grams', 'kilograms', 'packets', 'seeds'];

    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'crop' => ['nullable', 'string', 'max:100'],
        ]);

        $listings = SeedBankListing::query()
            ->with('farmer')
            ->where('status', 'available')
            ->where('quantity_available', '>', 0)
            ->whereHas('farmer', fn (Builder $query) => $query->where('role', 'farmer')->whereNotNull('farmer_verified_at'))
            ->when($filters['q'] ?? null, fn (Builder $query, string $term) => $query->where(function (Builder $query) use ($term) {
                $query->where('crop_name', 'like', '%'.$term.'%')
                    ->orWhere('variety', 'like', '%'.$term.'%')
                    ->orWhere('provenance', 'like', '%'.$term.'%');
            }))
            ->when($filters['crop'] ?? null, fn (Builder $query, string $crop) => $query->where('crop_name', $crop))
            ->latest()->paginate(12)->withQueryString();

        $crops = SeedBankListing::query()->where('status', 'available')->where('quantity_available', '>', 0)
            ->whereHas('farmer', fn (Builder $query) => $query->where('role', 'farmer')->whereNotNull('farmer_verified_at'))
            ->select('crop_name')->distinct()->orderBy('crop_name')->pluck('crop_name');

        return view('seed-bank.index', compact('listings', 'crops'));
    }

    public function manage(Request $request)
    {
        $farmerId = $request->user()->id;
        $listings = SeedBankListing::query()->with(['requests.requester'])
            ->when($request->user()->role !== 'admin', fn (Builder $query) => $query->where('farmer_id', $farmerId))
            ->latest()->paginate(20, ['*'], 'lots')->withQueryString();

        $receivedRequests = SeedBankRequest::query()->with(['listing.farmer', 'requester'])
            ->whereHas('listing', fn (Builder $query) => $query->when($request->user()->role !== 'admin', fn (Builder $query) => $query->where('farmer_id', $farmerId)))
            ->latest()->paginate(20, ['*'], 'received')->withQueryString();

        $sentRequests = SeedBankRequest::query()->with(['listing.farmer'])
            ->where('requester_id', $farmerId)->latest()->paginate(20, ['*'], 'sent')->withQueryString();

        return view('seed-bank.manage', ['listings' => $listings, 'receivedRequests' => $receivedRequests, 'sentRequests' => $sentRequests, 'units' => self::UNITS]);
    }

    public function store(Request $request)
    {
        $data = $this->validatedListing($request);
        $data['farmer_id'] = $request->user()->id;
        $data['location'] = $data['location'] ?: $request->user()->location_name;
        SeedBankListing::create($data);

        return back()->with('status', 'Seed lot added to your Seed Bank. It is visible to other farmers after your farm is verified.');
    }

    public function update(Request $request, SeedBankListing $listing)
    {
        $this->authorizeListing($request, $listing);
        $listing->update($this->validatedListing($request));

        return back()->with('status', 'Seed lot updated.');
    }

    public function requestSeed(Request $request, SeedBankListing $listing)
    {
        abort_unless($request->user()->role === 'admin' || ($request->user()->role === 'farmer' && $request->user()->farmer_verified_at), 403);
        abort_if($listing->farmer_id === $request->user()->id, 422, 'You cannot request a seed lot from your own farm.');

        $data = $request->validate([
            'quantity' => ['required', 'numeric', 'gt:0', 'max:100000000'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($listing, $request, $data): void {
            $lockedListing = SeedBankListing::query()->lockForUpdate()->findOrFail($listing->id);
            abort_unless($lockedListing->status === 'available' && (float) $lockedListing->quantity_available > 0, 422, 'This seed lot is no longer available.');
            abort_if((float) $data['quantity'] > (float) $lockedListing->quantity_available, 422, 'Requested quantity is greater than the available amount.');

            $lockedListing->requests()->create([
                'requester_id' => $request->user()->id,
                'quantity' => $data['quantity'],
                'unit' => $lockedListing->unit,
                'message' => $data['message'] ?? null,
            ]);
        });

        return back()->with('status', 'Seed request sent to the farmer. Stock changes only if the farmer accepts.');
    }

    public function respond(Request $request, SeedBankRequest $seedRequest)
    {
        $this->authorizeListing($request, $seedRequest->listing);
        $data = $request->validate(['status' => ['required', Rule::in(['accepted', 'rejected', 'fulfilled'])]]);

        DB::transaction(function () use ($seedRequest, $data): void {
            $lockedRequest = SeedBankRequest::query()->lockForUpdate()->findOrFail($seedRequest->id);
            $lockedListing = SeedBankListing::query()->lockForUpdate()->findOrFail($lockedRequest->seed_bank_listing_id);

            if ($data['status'] === 'accepted') {
                abort_unless($lockedRequest->status === 'pending', 422, 'Only pending requests can be accepted.');
                abort_if((float) $lockedRequest->quantity > (float) $lockedListing->quantity_available, 422, 'There is no longer enough seed stock to accept this request.');
                $lockedListing->quantity_available = (float) $lockedListing->quantity_available - (float) $lockedRequest->quantity;
                if ((float) $lockedListing->quantity_available <= 0) {
                    $lockedListing->quantity_available = 0;
                    $lockedListing->status = 'out_of_stock';
                }
                $lockedListing->save();
            } elseif ($data['status'] === 'rejected') {
                abort_unless($lockedRequest->status === 'pending', 422, 'Only pending requests can be rejected.');
            } else {
                abort_unless($lockedRequest->status === 'accepted', 422, 'Only accepted requests can be marked fulfilled.');
            }

            $lockedRequest->status = $data['status'];
            $lockedRequest->save();
        });

        return back()->with('status', 'Seed request status updated.');
    }

    private function validatedListing(Request $request): array
    {
        return $request->validate([
            'crop_name' => ['required', 'string', 'max:100'],
            'variety' => ['required', 'string', 'max:120'],
            'lot_code' => ['nullable', 'string', 'max:60'],
            'provenance' => ['required', 'string', 'max:180'],
            'harvest_date' => ['nullable', 'date', 'before_or_equal:today'],
            'germination_tested_at' => ['nullable', 'date', 'before_or_equal:today', 'required_with:germination_rate'],
            'germination_rate' => ['nullable', 'numeric', 'between:0,100', 'required_with:germination_tested_at'],
            'quantity_available' => ['required', 'numeric', 'min:0', 'max:100000000'],
            'unit' => ['required', Rule::in(self::UNITS)],
            'location' => ['nullable', 'string', 'max:150'],
            'storage_notes' => ['nullable', 'string', 'max:300'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['available', 'out_of_stock', 'archived'])],
        ]);
    }

    private function authorizeListing(Request $request, SeedBankListing $listing): void
    {
        abort_unless($request->user()->role === 'admin' || $listing->farmer_id === $request->user()->id, 403);
    }
}
