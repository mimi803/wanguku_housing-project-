<?php

namespace App\Http\Controllers;

use App\Services\ResourceMapClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('account.profile', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'phone' => ['required', 'string', 'max:20'],
            'location_name' => ['nullable', 'string', 'max:150'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'profile_photo' => ['nullable', 'image', 'max:3072'],
        ]);

        $user = $request->user();
        if ($user->role === 'farmer') {
            $data['show_on_map'] = $request->boolean('show_on_map')
                && isset($data['latitude'], $data['longitude'])
                && $data['latitude'] !== '' && $data['longitude'] !== '';
        }
        unset($data['profile_photo']);
        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo_path) Storage::disk('public')->delete($user->profile_photo_path);
            $data['profile_photo_path'] = $request->file('profile_photo')->store('profile-photos', 'public');
        }
        $user->forceFill($data)->save();

        $response = back()->with('status', 'Your profile has been updated.');
        if ($user->role === 'farmer') {
            $map = app(ResourceMapClient::class);
            $synced = $user->show_on_map ? $map->sync($user) : $map->remove($user);
            if ($user->show_on_map && ! $synced) {
                $response->with('integration_warning', 'Your profile is saved, but the Resource Center could not be updated. Check the map connection settings.');
            }
        }

        return $response;
    }
}
