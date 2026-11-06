<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/Profile', [
            'user' => $request->user()->only(['first_name', 'middle_name', 'last_name', 'email', 'avatar']),
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        if ($request->hasFile('avatar')) {
            $newAvatar = $request->file('avatar')->store('avatars', 'public');
        } else {
            $newAvatar = null;
        }

        $user->fill(collect($validated)->only(['first_name', 'middle_name', 'last_name', 'email'])->all());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($newAvatar !== null) {
            $oldAvatar = $user->avatar;
            $user->avatar = $newAvatar;
        } elseif ($request->boolean('remove_avatar')) {
            $oldAvatar = $user->avatar;
            $user->avatar = null;
        } else {
            $oldAvatar = null;
        }

        $user->save();

        if ($oldAvatar) {
            Storage::disk('public')->delete($oldAvatar);
        }

        return to_route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's profile.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
