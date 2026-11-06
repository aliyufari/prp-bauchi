<?php

namespace App\Http\Middleware;

use App\Enums\EducationStatus;
use App\Support\{Access, Jurisdiction, NavigationBuilder};
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');
        $u = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'educationStatuses' => EducationStatus::options(),
            'quote' => ['message' => trim($message), 'author' => trim($author)],
            'auth' => fn () => $u ? [
            'user' => [
                    'id' => $u->id, 'first_name' => $u->first_name, 'last_name' => $u->last_name,
                    'email' => $u->email,
                    'avatar' => $u->avatar,
                    'avatar_url' => $u->avatar ? asset('storage/'.$u->avatar) : null,
                ],
                'roles' => $u->getRoleNames()->values(),
                'permissions' => $u->getAllPermissions()->pluck('name')->values(),
                'jurisdiction' => $this->jurisdiction($u),
                'navigation' => NavigationBuilder::for($u),
                'layout' => Access::layoutFor($u),
                'home' => Access::homeFor($u),
            ] : ['user' => null, 'roles' => [], 'permissions' => [], 'jurisdiction' => null, 'navigation' => [], 'layout' => 'app', 'home' => '/'],
            'ziggy' => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'flash' => fn () => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'warning' => $request->session()->get('warning'),
            ],
        ];
    }

    private function jurisdiction($u): array
    {
        $j = Jurisdiction::for($u);

        return [
            'level' => $j->level,
            'id' => $j->id,
            'name' => $j->name(),
            'unrestricted' => $j->unrestricted,
            'locked' => (object) $j->locked(), // `{}` rather than `[]` when empty
        ];
    }
}
