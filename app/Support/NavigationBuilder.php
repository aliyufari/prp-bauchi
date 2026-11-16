<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Route;

final class NavigationBuilder
{
    public static function for(User $user): array
    {
        $key = Access::layoutFor($user) === 'app' ? 'management' : 'portal';
        $groups = [];

        foreach (config("navigation.{$key}", []) as $group) {
            $items = [];

            foreach ($group['items'] as $item) {
                if (isset($item['can']) && ! $user->checkPermissionTo($item['can'])) {
                    continue;
                }

                // Optional: show only to users working at this jurisdiction level (e.g. ward agents).
                if (isset($item['level']) && Jurisdiction::for($user)->level !== $item['level']) {
                    continue;
                }

                $ready = Route::has($item['route']);

                // No route and no planned path: hide it. With a path: show it as "coming soon".
                if (! $ready && ! isset($item['path'])) {
                    continue;
                }

                $items[] = [
                    'label' => $item['label'],
                    'href' => $ready ? route($item['route'], [], false) : $item['path'],
                    'icon' => $item['icon'],
                    'exact' => $item['exact'] ?? false,
                    'featured' => $item['featured'] ?? false,
                    'ready' => $ready,
                ];
            }

            if ($items) {
                $groups[] = ['group' => $group['group'], 'items' => $items];
            }
        }

        return $groups;
    }
}
