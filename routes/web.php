<?php

use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\LocationOptionsController;
use App\Http\Controllers\Dashboard\PortalController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

require __DIR__.'/auth.php';

Route::middleware('auth')->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('portal', [PortalController::class, 'home'])->name('portal.home');
});

require __DIR__.'/dashboard.php';

require __DIR__.'/settings.php';
