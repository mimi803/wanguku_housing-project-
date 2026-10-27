<?php

namespace App\Http\Controllers;

use App\Services\ResourceMapClient;
use App\Services\KenyanPhoneNumber;
use App\Services\PhoneVerificationCodes;
use App\Models\User;
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
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'email', 'max:100'],
            'phone' => ['required', 'string', 'max:20'],
            'location_name' => ['nullable', 'string', 'max:150'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'profile_photo' => ['nullable', 'image', 'max:3072'],
        ]);

        $data['email'] = mb_strtolower($data['email']);
        if (User::query()->whereRaw('LOWER(email) = ?', [$data['email']])->whereKeyNot($user->getKey())->exists()) {
            return back()->withErrors(['email' => 'That email address is already associated with another account.'])->withInput();
        }

        $phoneService = app(KenyanPhoneNumber::class);
        $newPhone = $phoneService->normalize($data['phone']);
        if (! $newPhone) {
            return back()->withErrors(['phone' => 'Enter a valid Kenyan mobile number, such as 0712 345 678.'])->withInput();
        }

        $currentPhone = $phoneService->normalize((string) $user->phone);
        $changingPhone = $newPhone !== $currentPhone;
        if ($changingPhone && User::query()->whereIn('phone', $phoneService->lookupVariants($newPhone))->whereKeyNot($user->getKey())->exists()) {
            return back()->withErrors(['phone' => 'That phone number is already linked to another account.'])->withInput();
        }

        if ($changingPhone) {
            try {
                app(PhoneVerificationCodes::class)->send($newPhone, 'change_phone');
            } catch (\RuntimeException $exception) {
                $message = str_contains($exception->getMessage(), 'Too many')
                    ? $exception->getMessage()
                    : 'We could not send a verification code. Check that Farmers Connect SMS is configured, then try again.';

                return back()->withErrors(['phone' => $message])->withInput();
            }

            $request->session()->put('pending_phone_verification', [
                'phone' => $newPhone,
                'purpose' => 'change_phone',
                'user_id' => $user->id,
            ]);
        }

        $data['phone'] = $changingPhone ? $user->phone : $newPhone;
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

        $response = $changingPhone
            ? redirect()->route('phone.verify')->with('status', 'Your profile was saved. Verify your new phone number to finish updating your account.')
            : back()->with('status', 'Your profile has been updated.');
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
