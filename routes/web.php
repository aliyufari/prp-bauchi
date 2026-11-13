<?php

use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\PortalController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/
Route::get('/', fn () => Inertia::render('Welcome'))->name('home');

/*
|--------------------------------------------------------------------------
| Guest + authentication (login, register, forgot / reset password, logout)
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| Signed-in entry points
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('portal', [PortalController::class, 'home'])->name('portal.home');
});

/*
|--------------------------------------------------------------------------
| Dashboard modules (members, coordinators, results, location options, ...)
|--------------------------------------------------------------------------
*/
require __DIR__.'/dashboard.php';

/*
|--------------------------------------------------------------------------
| Account settings (profile + password)
|--------------------------------------------------------------------------
*/
require __DIR__.'/settings.php';
