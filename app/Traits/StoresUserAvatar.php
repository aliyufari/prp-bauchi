<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

trait StoresUserAvatar
{
    protected function syncUserAvatar(User $user, Request $request): void
    {
        if (!$request->hasFile('avatar')) {
            return;
        }

        $old = $user->getRawOriginal('avatar');
        $path = $request->file('avatar')->store('avatars', 'public');
        $user->forceFill(['avatar' => $path])->save();

        if ($old && !str_starts_with($old, 'http')) {
            Storage::disk('public')->delete($old);
        }
    }
}
