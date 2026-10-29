<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Route;

final class Access
{
    public static function layoutFor(User $user): string
    {
        return $user->checkPermissionTo('dashboard.view') ? 'app' : 'portal';
    }

    public static function homeFor(User $user): string
    {
        $name = self::layoutFor($user) === 'app' ? 'dashboard' : 'portal.home';

        return Route::has($name) ? route($name, [], false) : '/';
    }
}