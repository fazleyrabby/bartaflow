<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Enums\WorkspaceStatus;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

it('shows the registration page', function () {
    $this->get(route('register'))->assertOk()->assertSee('Create your account');
});

it('registers a user and atomically creates a workspace with owner membership', function () {
    Event::fake([Registered::class]);

    $this->post(route('register'), [
        'name' => 'Rahim Chowdhury',
        'email' => 'rahim@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertRedirect(route('verification.notice'));

    $user = User::where('email', 'rahim@example.com')->firstOrFail();

    // User created
    expect($user->name)->toBe('Rahim Chowdhury');

    // Default workspace created from user's name
    $workspace = Workspace::where('owner_id', $user->id)->firstOrFail();
    expect($workspace->name)->toBe("Rahim Chowdhury's Workspace")
        ->and($workspace->status)->toBe(WorkspaceStatus::Active);

    // Owner membership created
    $this->assertDatabaseHas('workspace_users', [
        'workspace_id' => $workspace->id,
        'user_id' => $user->id,
        'role' => Role::Owner->value,
        'status' => 'active',
    ]);

    // Registered event fired
    Event::assertDispatched(Registered::class);
});

it('uses the provided workspace name on registration', function () {
    $this->post(route('register'), [
        'name' => 'Nadia Islam',
        'email' => 'nadia@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'workspace_name' => 'Nadia Fashion House',
    ]);

    $user = User::where('email', 'nadia@example.com')->firstOrFail();
    $workspace = Workspace::where('owner_id', $user->id)->firstOrFail();

    expect($workspace->name)->toBe('Nadia Fashion House');
});

it('rejects duplicate email on registration', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->post(route('register'), [
        'name' => 'Someone',
        'email' => 'taken@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertSessionHasErrors('email');
});

it('validates minimum password length', function () {
    $this->post(route('register'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'short',
        'password_confirmation' => 'short',
    ])->assertSessionHasErrors('password');
});

it('requires passwords to match', function () {
    $this->post(route('register'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password123',
        'password_confirmation' => 'different',
    ])->assertSessionHasErrors('password');
});

it('logs in the user after registration', function () {
    $this->post(route('register'), [
        'name' => 'Auto Login',
        'email' => 'autologin@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $this->assertAuthenticated();
});

it('redirects authenticated users away from register page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('register'))->assertRedirect();
});
