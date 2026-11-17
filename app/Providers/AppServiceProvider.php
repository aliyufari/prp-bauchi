<?php

namespace App\Providers;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Schema;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use App\Models\UserLoginEvent;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        JsonResource::withoutWrapping();

        Schema::defaultStringLength(191);

        Event::listen(Login::class, function (Login $event): void {
            $request = request();
            $user = $event->user;
            $agent = (string) $request->userAgent();
            $browser = match (true) {
                str_contains($agent, 'Edg/') => 'Microsoft Edge',
                str_contains($agent, 'OPR/') => 'Opera',
                str_contains($agent, 'Firefox/') => 'Firefox',
                str_contains($agent, 'Chrome/') && ! str_contains($agent, 'Edg/') => 'Chrome',
                str_contains($agent, 'Safari/') && ! str_contains($agent, 'Chrome/') => 'Safari',
                default => 'Unknown browser',
            };
            $platform = match (true) {
                str_contains($agent, 'Windows') => 'Windows',
                str_contains($agent, 'Android') => 'Android',
                str_contains($agent, 'iPhone') || str_contains($agent, 'iPad') => 'iOS',
                str_contains($agent, 'Mac OS') => 'macOS',
                str_contains($agent, 'Linux') => 'Linux',
                default => 'Unknown platform',
            };
            $device = (str_contains($agent, 'Mobile') || str_contains($agent, 'Android') || str_contains($agent, 'iPhone')) ? 'Mobile' : 'Desktop';
            $now = now();
            $user->forceFill([
                'last_login_at' => $now, 'last_login_ip' => $request->ip(),
                'last_login_browser' => $browser, 'last_login_device' => $device.' · '.$platform,
            ])->save();
            UserLoginEvent::create([
                'user_id' => $user->id, 'email' => $user->email, 'ip_address' => $request->ip(),
                'user_agent' => $agent, 'browser' => $browser, 'platform' => $platform,
                'device' => $device, 'logged_in_at' => $now,
            ]);
        });
    }
}
