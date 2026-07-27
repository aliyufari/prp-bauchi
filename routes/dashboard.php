<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\PuController;
use App\Http\Controllers\Dashboard\MemberController;
use App\Http\Controllers\Dashboard\AspirantController;
use App\Http\Controllers\Dashboard\PartyAgentController;
use App\Http\Controllers\Dashboard\CoordinatorController;
use App\Http\Controllers\Dashboard\StakeholderController;
use App\Http\Controllers\Dashboard\CampaignTeamController;
use App\Http\Controllers\Dashboard\ReturningOfficerController;
use App\Http\Controllers\Dashboard\SupervisoryAgentController;
use App\Http\Controllers\Dashboard\CampaignTeamMemberController;

Route::prefix('dashboard/members')->name('members.')->group(function () {
    Route::get('/', [MemberController::class, 'index'])->name('index');
    Route::post('/', [MemberController::class, 'store'])->name('store');
    Route::patch('/{member}', [MemberController::class, 'update'])->name('update');
    Route::get('/{member}', [MemberController::class, 'show'])->name('show');
    Route::delete('/{member}', [MemberController::class, 'destroy'])->name('destroy');
    Route::post('/bulk-destroy', [MemberController::class, 'bulkDestroy'])->name('bulk-destroy');
    Route::get('/export', [MemberController::class, 'export'])->name('export');
});

Route::prefix('dashboard/party-agents')->name('party-agents.')->group(function () {
    Route::get('/', [PartyAgentController::class, 'index'])->name('index');
    Route::post('/', [PartyAgentController::class, 'store'])->name('store');
    Route::patch('/{party_agent}', [PartyAgentController::class, 'update'])->name('update');
    Route::get('/{party_agent}', [PartyAgentController::class, 'show'])->name('show');
    Route::delete('/{party_agent}', [PartyAgentController::class, 'destroy'])->name('destroy');
    Route::post('/bulk-destroy', [PartyAgentController::class, 'bulkDestroy'])->name('bulk-destroy');
    Route::get('/export', [PartyAgentController::class, 'export'])->name('export');
});

Route::prefix('dashboard/coordinators')->name('coordinators.')->group(function () {
    Route::get('/', [CoordinatorController::class, 'index'])->name('index');
    Route::post('/', [CoordinatorController::class, 'store'])->name('store');
    Route::patch('/{coordinator}', [CoordinatorController::class, 'update'])->name('update');
    Route::get('/{coordinator}', [CoordinatorController::class, 'show'])->name('show');
    Route::delete('/{coordinator}', [CoordinatorController::class, 'destroy'])->name('destroy');
    Route::post('/bulk-destroy', [CoordinatorController::class, 'bulkDestroy'])->name('bulk-destroy');
    Route::get('/export', [CoordinatorController::class, 'export'])->name('export');
});

Route::prefix('dashboard/stakeholders')->name('stakeholders.')->group(function () {
    Route::get('/', [StakeholderController::class, 'index'])->name('index');
    Route::post('/', [StakeholderController::class, 'store'])->name('store');
    Route::patch('/{stakeholder}', [StakeholderController::class, 'update'])->name('update');
    Route::get('/{stakeholder}', [StakeholderController::class, 'show'])->name('show');
    Route::delete('/{stakeholder}', [StakeholderController::class, 'destroy'])->name('destroy');
    Route::post('/bulk-destroy', [StakeholderController::class, 'bulkDestroy'])->name('bulk-destroy');
    Route::get('/export', [StakeholderController::class, 'export'])->name('export');
});

Route::prefix('dashboard/returning-officers')->name('returning-officers.')->group(function () {
    Route::get('/', [ReturningOfficerController::class, 'index'])->name('index');
    Route::post('/', [ReturningOfficerController::class, 'store'])->name('store');
    Route::patch('/{returning_officer}', [ReturningOfficerController::class, 'update'])->name('update');
    Route::get('/{returning_officer}', [ReturningOfficerController::class, 'show'])->name('show');
    Route::delete('/{returning_officer}', [ReturningOfficerController::class, 'destroy'])->name('destroy');
    Route::post('/bulk-destroy', [ReturningOfficerController::class, 'bulkDestroy'])->name('bulk-destroy');
    Route::get('/export', [ReturningOfficerController::class, 'export'])->name('export');
});

Route::prefix('dashboard/supervisory-agents')->name('supervisory-agents.')->group(function () {
    Route::get('/', [SupervisoryAgentController::class, 'index'])->name('index');
    Route::post('/', [SupervisoryAgentController::class, 'store'])->name('store');
    Route::patch('/{supervisory_agent}', [SupervisoryAgentController::class, 'update'])->name('update');
    Route::get('/{supervisory_agent}', [SupervisoryAgentController::class, 'show'])->name('show');
    Route::delete('/{supervisory_agent}', [SupervisoryAgentController::class, 'destroy'])->name('destroy');
    Route::post('/bulk-destroy', [SupervisoryAgentController::class, 'bulkDestroy'])->name('bulk-destroy');
    Route::get('/export', [SupervisoryAgentController::class, 'export'])->name('export');
});

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
    Route::patch('/{polling_unit}', [PuController::class, 'update'])->name('update');
    Route::get('/{polling_unit}', [PuController::class, 'show'])->name('show');
    Route::delete('/{polling_unit}', [PuController::class, 'destroy'])->name('destroy');
    Route::post('/bulk-destroy', [PuController::class, 'bulkDestroy'])->name('bulk-destroy');
    Route::get('/export', [PuController::class, 'export'])->name('export');
});

Route::prefix('dashboard/campaign-teams')->name('campaign-teams.')->group(function () {
    Route::get('/', [CampaignTeamController::class, 'index'])->name('index');
    Route::post('/', [CampaignTeamController::class, 'store'])->name('store');
    Route::get('/{campaign_team}', [CampaignTeamController::class, 'show'])->name('show');
    Route::patch('/{campaign_team}', [CampaignTeamController::class, 'update'])->name('update');
    Route::delete('/{campaign_team}', [CampaignTeamController::class, 'destroy'])->name('destroy');

    Route::post('/{campaign_team}/members', [CampaignTeamMemberController::class, 'store'])->name('members.store');
    Route::patch('/{campaign_team}/members/{member}', [CampaignTeamMemberController::class, 'update'])->name('members.update');
    Route::delete('/{campaign_team}/members/{member}', [CampaignTeamMemberController::class, 'destroy'])->name('members.destroy');
});