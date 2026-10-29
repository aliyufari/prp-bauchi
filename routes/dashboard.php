<?php

use App\Http\Controllers\Dashboard\AspirantController;
use App\Http\Controllers\Dashboard\CampaignTeamController;
use App\Http\Controllers\Dashboard\CampaignTeamMemberController;
use App\Http\Controllers\Dashboard\LocationOptionsController;
use App\Http\Controllers\Dashboard\MemberController;
use App\Http\Controllers\Dashboard\PuController;
use App\Http\Controllers\Dashboard\ResultController;

use App\Http\Controllers\Dashboard\{
    CoordinatorController, PartyAgentController, ReturningOfficerController,
    StakeholderController, SupervisoryAgentController,
};

use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::prefix('dashboard/members')->name('members.')->group(function () {
        Route::get('/', [MemberController::class, 'index'])->name('index');
        Route::post('/', [MemberController::class, 'store'])->name('store');
        Route::get('export', [MemberController::class, 'export'])->name('export');
        Route::post('bulk-destroy', [MemberController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::get('{member}', [MemberController::class, 'show'])->name('show');
        Route::patch('{member}', [MemberController::class, 'update'])->name('update');
        Route::delete('{member}', [MemberController::class, 'destroy'])->name('destroy');
    });

    $people = [
        'party-agents'       => [PartyAgentController::class,       'party_agent'],
        'coordinators'       => [CoordinatorController::class,      'coordinator'],
        'stakeholders'       => [StakeholderController::class,      'stakeholder'],
        'returning-officers' => [ReturningOfficerController::class, 'returning_officer'],
        'supervisory-agents' => [SupervisoryAgentController::class, 'supervisory_agent'],
    ];

    foreach ($people as $uri => [$controller, $param]) {
        Route::prefix("dashboard/{$uri}")->name("{$uri}.")->group(function () use ($controller, $param) {
            Route::get('/', [$controller, 'index'])->name('index');
            Route::post('/', [$controller, 'store'])->name('store');
            Route::get('export', [$controller, 'export'])->name('export');
            Route::post('bulk-destroy', [$controller, 'bulkDestroy'])->name('bulk-destroy');
            Route::get("{{$param}}", [$controller, 'show'])->name('show');
            Route::patch("{{$param}}", [$controller, 'update'])->name('update');
            Route::delete("{{$param}}", [$controller, 'destroy'])->name('destroy');
        });
    }

    Route::get('dashboard/aspirant', [AspirantController::class, 'governor'])->name('aspirant.show');
    Route::prefix('dashboard/aspirants')->name('aspirants.')->group(function () {
        Route::get('/', [AspirantController::class, 'index'])->name('index');
        Route::post('/', [AspirantController::class, 'store'])->name('store');
        Route::patch('/{aspirant}', [AspirantController::class, 'update'])->name('update');
        Route::get('/{aspirant}', [AspirantController::class, 'show'])->name('show');
        Route::delete('/{aspirant}', [AspirantController::class, 'destroy'])->name('destroy');
        Route::post('/bulk-destroy', [AspirantController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::get('/export', [AspirantController::class, 'export'])->name('export');
    });

    Route::prefix('dashboard/polling-units')->name('polling-units.')->group(function () {
        Route::get('/', [PuController::class, 'index'])->name('index');
        Route::post('/', [PuController::class, 'store'])->name('store');
        Route::get('export', [PuController::class, 'export'])->name('export');
        Route::post('bulk-destroy', [PuController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::patch('{polling_unit}', [PuController::class, 'update'])->name('update');
        Route::delete('{polling_unit}', [PuController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('dashboard/campaign-teams')->name('campaign-teams.')->group(function () {
        Route::get('/', [CampaignTeamController::class, 'index'])->name('index');
        Route::post('/', [CampaignTeamController::class, 'store'])->name('store');
        Route::get('{campaign_team}', [CampaignTeamController::class, 'show'])->name('show');
        Route::patch('{campaign_team}', [CampaignTeamController::class, 'update'])->name('update');
        Route::delete('{campaign_team}', [CampaignTeamController::class, 'destroy'])->name('destroy');
        Route::post('{campaign_team}/members', [CampaignTeamMemberController::class, 'store'])->name('members.store');
        Route::patch('{campaign_team}/members/{team_member}', [CampaignTeamMemberController::class, 'update'])->name('members.update');
        Route::delete('{campaign_team}/members/{team_member}', [CampaignTeamMemberController::class, 'destroy'])->name('members.destroy');
    });

    Route::prefix('dashboard/location-options')->name('location-options.')->group(function () {
        Route::get('zones', [LocationOptionsController::class, 'zones'])->name('zones');
        Route::get('lgas', [LocationOptionsController::class, 'lgas'])->name('lgas');
        Route::get('wards', [LocationOptionsController::class, 'wards'])->name('wards');
        Route::get('pus', [LocationOptionsController::class, 'pus'])->name('pus');
    });

    Route::prefix('dashboard/results')->name('results.')->group(function () {
        Route::get('/', [ResultController::class, 'index'])->name('index');
        Route::post('/', [ResultController::class, 'store'])->name('store');
        Route::patch('{result}', [ResultController::class, 'update'])->name('update');
        Route::patch('{result}/verify', [ResultController::class, 'verify'])->name('verify');
        Route::delete('{result}', [ResultController::class, 'destroy'])->name('destroy');
    });
});