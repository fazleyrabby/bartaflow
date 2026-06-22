<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceUser;
use App\Services\Tenancy\CurrentWorkspace;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ── Helper: create a user with a workspace, set CurrentWorkspace in the container ──

function userWithWorkspace(string $role = 'owner'): array
{
    $owner = User::factory()->create();
    $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
    WorkspaceUser::create([
        'workspace_id' => $workspace->id,
        'user_id'      => $owner->id,
        'role'         => Role::Owner->value,
        'status'       => 'active',
        'joined_at'    => now(),
    ]);

    return [$owner, $workspace];
}

it('sets the current workspace via EnsureWorkspace middleware on the dashboard', function () {
    [$user, $workspace] = userWithWorkspace();

    $this->actingAs($user)
        ->withSession(['workspace_id' => $workspace->id])
        ->get(route('dashboard'))
        ->assertOk();

    expect(app(CurrentWorkspace::class)->isSet())->toBeTrue();
    expect(app(CurrentWorkspace::class)->id())->toBe($workspace->id);
});

it('falls back to the first workspace when no workspace_id is in session', function () {
    [$user, $workspace] = userWithWorkspace();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();

    expect(app(CurrentWorkspace::class)->id())->toBe($workspace->id);
});

it('logs out and redirects when user has no active workspace membership', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('login'));
});

it('rejects a non-member from accessing another workspace via session', function () {
    [$owner, $workspace] = userWithWorkspace();
    $stranger = User::factory()->create();

    // Stranger tries to use another workspace's ID in their session.
    $this->actingAs($stranger)
        ->withSession(['workspace_id' => $workspace->id])
        ->get(route('dashboard'))
        ->assertRedirect(route('login'));
});

it('WorkspaceScope filters queries by current workspace', function () {
    [$user1, $ws1] = userWithWorkspace();
    [$user2, $ws2] = userWithWorkspace();

    // Set ws1 as current.
    app(CurrentWorkspace::class)->set($ws1);

    // Workspace queries should return ws1 only via the scope on tenant models.
    // (Workspace itself is not tenant-scoped, but Invitation is.)
    $count = \App\Models\Invitation::count();
    expect($count)->toBe(0); // No invitations; scope is active.

    // Confirm scope is correctly filtering by workspace_id.
    $invitation = \App\Models\Invitation::withoutGlobalScopes()->create([
        'workspace_id' => $ws2->id,
        'invited_by'   => $user2->id,
        'email'        => 'test@example.com',
        'role'         => Role::Staff->value,
        'token'        => \Illuminate\Support\Str::random(64),
        'status'       => 'pending',
        'expires_at'   => now()->addDays(7),
    ]);

    // With scope for ws1, ws2's invitation is invisible.
    expect(\App\Models\Invitation::count())->toBe(0);

    // Switching scope to ws2 makes it visible.
    app(CurrentWorkspace::class)->set($ws2);
    expect(\App\Models\Invitation::count())->toBe(1);
});

it('workspace switcher updates session and redirects to dashboard', function () {
    [$user, $ws1] = userWithWorkspace();

    // Create a second workspace the user belongs to.
    $ws2 = Workspace::factory()->create(['owner_id' => $user->id]);
    WorkspaceUser::create([
        'workspace_id' => $ws2->id,
        'user_id'      => $user->id,
        'role'         => Role::Owner->value,
        'status'       => 'active',
        'joined_at'    => now(),
    ]);

    $this->actingAs($user)
        ->withSession(['workspace_id' => $ws1->id])
        ->post(route('workspaces.switch'), ['workspace_id' => $ws2->id])
        ->assertRedirect(route('dashboard'));

    expect(session('workspace_id'))->toBe($ws2->id);
});

it('workspace switcher rejects a workspace the user does not belong to', function () {
    [$user, $ws1] = userWithWorkspace();
    [, $otherWs] = userWithWorkspace();

    $this->actingAs($user)
        ->withSession(['workspace_id' => $ws1->id])
        ->post(route('workspaces.switch'), ['workspace_id' => $otherWs->id])
        ->assertForbidden();
});
