<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\UserLoginEvent;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LoginActivityController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->hasAnyRole(['super_admin', 'admin']), 403);

        $search = $request->input('search');
        $events = UserLoginEvent::query()->with('user:id,first_name,middle_name,last_name,email')
            ->when($search, fn ($q) => $q->where(fn ($q) => $q
                ->where('email', 'like', '%'.$search.'%')
                ->orWhere('ip_address', 'like', '%'.$search.'%')
                ->orWhere('browser', 'like', '%'.$search.'%')
                ->orWhere('platform', 'like', '%'.$search.'%')
                ->orWhere('device', 'like', '%'.$search.'%')
                ->orWhere('user_agent', 'like', '%'.$search.'%')
                ->orWhereHas('user', fn ($u) => $u->where('first_name', 'like', '%'.$search.'%')
                    ->orWhere('last_name', 'like', '%'.$search.'%'))))
            ->latest('logged_in_at')->paginate(30)->withQueryString();

        return Inertia::render('dashboard/login-activity/Index', [
            'events' => $events, 'filters' => $request->only('search'),
        ]);
    }
}
