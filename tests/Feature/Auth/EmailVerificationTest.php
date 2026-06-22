<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceUser;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

it('shows the verification notice for unverified users', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('verification.notice'))
        ->assertOk()
        ->assertSee('Check your email');
});

it('redirects already-verified users from the notice page', function () {
    $user = User::factory()->create(); // email_verified_at is set by default factory

    $this->actingAs($user)
        ->get(route('verification.notice'))
        ->assertRedirect(route('dashboard'));
});

it('verifies email via signed link and fires the Verified event', function () {
    Event::fake([Verified::class]);

    $user = User::factory()->unverified()->create();

    $url = URL::signedRoute('verification.verify', [
        'id' => $user->id,
        'hash' => sha1($user->email),
    ]);

    $this->actingAs($user)->get($url)->assertRedirect(route('dashboard'));

    $user->refresh();
    expect($user->hasVerifiedEmail())->toBeTrue();
    Event::assertDispatched(Verified::class);
});

it('does not verify with an invalid signature', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('verification.verify', ['id' => $user->id, 'hash' => 'invalid-hash']))
        ->assertForbidden();
});

it('sends a new verification email when requested', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->post(route('verification.send'))
        ->assertRedirect();
});

it('blocks unverified users from accessing verified-only routes', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('verification.notice'));
});

it('allows verified users to access protected routes', function () {
    $user = User::factory()->create();
    $ws = Workspace::factory()->create(['owner_id' => $user->id]);
    WorkspaceUser::create([
        'workspace_id' => $ws->id,
        'user_id' => $user->id,
        'role' => Role::Owner->value,
        'status' => 'active',
        'joined_at' => now(),
    ]);

    $this->actingAs($user)
        ->withSession(['workspace_id' => $ws->id])
        ->get(route('dashboard'))
        ->assertOk();
});
