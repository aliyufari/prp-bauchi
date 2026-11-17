<?php

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use App\Support\Access;
use App\Models\Permission;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('guests are redirected to the login page', function () {
    $response = $this->get('/dashboard');
    $response->assertRedirect('/login');
});

test('authenticated users with dashboard access can visit the dashboard', function () {
    $permission = Permission::findOrCreate('dashboard.view', 'web');
    $user = User::factory()->create();
    $user->givePermissionTo($permission);

    $this->actingAs($user)->get('/dashboard')->assertStatus(200);
});

test('party agents use the portal dashboard and receive PU task permissions', function () {
    $this->seed(PermissionSeeder::class);

    $user = User::factory()->create();
    $user->assignRole(Role::PARTY_AGENT->value);

    expect($user->checkPermissionTo('pu-tasks.manage'))->toBeTrue()
        ->and($user->checkPermissionTo('dashboard.view'))->toBeFalse()
        ->and(Access::layoutFor($user))->toBe('portal');

    $this->actingAs($user)->get('/dashboard')->assertRedirect(route('portal.home'));
});
