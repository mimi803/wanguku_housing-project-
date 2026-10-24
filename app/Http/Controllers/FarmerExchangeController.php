<?php

namespace App\Http\Controllers;

use App\Models\FarmerExchangePost;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FarmerExchangeController extends Controller
{
    private const RESOURCE_TYPES = ['seeds' => 'Seeds & seedlings', 'animal_feed' => 'Animal feed', 'equipment' => 'Tools & equipment', 'labour' => 'Labour support', 'knowledge' => 'Knowledge & skills', 'produce' => 'Farm produce', 'other' => 'Other resource'];

    public function index(Request $request)
    {
        $posts = FarmerExchangePost::with('farmer')
            ->whereIn('status', ['open', 'fulfilled'])
            ->latest()->paginate(24)->withQueryString();

        return view('dashboard.network', ['posts' => $posts, 'resources' => self::RESOURCE_TYPES]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'post_kind' => ['required', Rule::in(['offer', 'request'])],
            'resource_type' => ['required', Rule::in(array_keys(self::RESOURCE_TYPES))],
            'title' => ['required', 'string', 'max:150'],
            'details' => ['required', 'string', 'max:2000'],
            'quantity' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
            'unit' => ['nullable', 'string', 'max:32'],
            'location' => ['nullable', 'string', 'max:150'],
        ]);
        $data['farmer_id'] = $request->user()->id;
        $data['location'] = $data['location'] ?: $request->user()->location_name;
        FarmerExchangePost::create($data);

        return back()->with('status', 'Your farmer network post is now visible to other farmers.');
    }

    public function updateStatus(Request $request, FarmerExchangePost $post)
    {
        abort_unless($request->user()->role === 'admin' || $post->farmer_id === $request->user()->id, 403);
        $data = $request->validate(['status' => ['required', Rule::in(['open', 'fulfilled', 'closed'])]]);
        $post->update($data);

        return back()->with('status', 'Network post updated.');
    }
}
