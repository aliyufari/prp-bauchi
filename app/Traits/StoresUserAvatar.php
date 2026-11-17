<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

trait StoresUserAvatar
{
    protected function syncUserAvatar(User $user, Request $request): void
    {
        if (! $request->hasFile('avatar')) {
            return;
        }

        $file = $request->file('avatar');
        if (! $file || ! $file->isValid()) {
            throw new RuntimeException('The selected profile photo could not be uploaded. Please choose it again.');
        }

        $disk = Storage::disk('public');
        $path = $file->store('avatars', 'public');
        if (! $path || ! $disk->exists($path)) {
            throw new RuntimeException('The profile photo could not be saved. Check that public storage is writable.');
        }

        $old = $user->getRawOriginal('avatar');
        $user->forceFill(['avatar' => $path])->save();

        if ($old && ! str_starts_with($old, 'http') && $old !== $path) {
            $disk->delete($old);
        }
    }
}
