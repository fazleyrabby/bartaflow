<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceUser;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeWorkspaceWithOwner(): array
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

function addMember(Workspace $workspace, User $user, Role $role): void
{
    WorkspaceUser::create([
        'workspace_id' => $workspace->id,
        'user_id'      => $user->id,
        'role'         => $role->value,
        'status'       => 'active',
        'joined_at'    => now(),
    ]);
}

it('shows the workspace settings page to owner', function () {
    [$owner, $workspace] = makeWorkspaceWithOwner();

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $workspace->id])
        ->get(route('settings.workspace'))
        ->assertOk()
        ->assertSee($workspace->name);
});

it('shows the workspace settings page to admin', function () {
    [$owner, $workspace] = makeWorkspaceWithOwner();
    $admin = User::factory()->create();
    addMember($workspace, $admin, Role::Admin);

    $this->actingAs($admin)
        ->withSession(['workspace_id' => $workspace->id])
        ->get(route('settings.workspace'))
        ->assertOk();
});

it('denies workspace settings to staff', function () {
    [$owner, $workspace] = makeWorkspaceWithOwner();
    $staff = User::factory()->create();
    addMember($workspace, $staff, Role::Staff);

    $this->actingAs($staff)
        ->withSession(['workspace_id' => $workspace->id])
        ->get(route('settings.workspace'))
        ->assertForbidden();
});

it('owner can update workspace name and timezone', function () {
    [$owner, $workspace] = makeWorkspaceWithOwner();

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $workspace->id])
        ->patch(route('settings.workspace.update'), [
            'name'     => 'New Workspace Name',
            'timezone' => 'Asia/Kolkata',
        ])
        ->assertRedirect(route('settings.workspace'));

    expect($workspace->fresh()->name)->toBe('New Workspace Name');
    expect($workspace->fresh()->timezone)->toBe('Asia/Kolkata');
});

it('owner can set business_name in workspace settings', function () {
    [$owner, $workspace] = makeWorkspaceWithOwner();

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $workspace->id])
        ->patch(route('settings.workspace.update'), [
            'name'          => $workspace->name,
            'timezone'      => $workspace->timezone,
            'business_name' => 'My Fancy Shop',
        ])
        ->assertRedirect();

    expect($workspace->fresh()->businessName())->toBe('My Fancy Shop');
});

it('owner can transfer ownership to another member', function () {
    [$owner, $workspace] = makeWorkspaceWithOwner();
    $admin = User::factory()->create();
    addMember($workspace, $admin, Role::Admin);

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $workspace->id])
        ->post(route('settings.workspace.transfer'), ['user_id' => $admin->id])
        ->assertRedirect();

    // New owner is the admin.
    expect($workspace->fresh()->owner_id)->toBe($admin->id);

    // Old owner is now admin.
    $oldOwnerMembership = WorkspaceUser::where('workspace_id', $workspace->id)
        ->where('user_id', $owner->id)
        ->first();
    expect($oldOwnerMembership->role)->toBe(Role::Admin);

    // New owner has Owner role.
    $newOwnerMembership = WorkspaceUser::where('workspace_id', $workspace->id)
        ->where('user_id', $admin->id)
        ->first();
    expect($newOwnerMembership->role)->toBe(Role::Owner);
});

it('admin cannot transfer ownership', function () {
    [$owner, $workspace] = makeWorkspaceWithOwner();
    $admin = User::factory()->create();
    addMember($workspace, $admin, Role::Admin);

    $this->actingAs($admin)
        ->withSession(['workspace_id' => $workspace->id])
        ->post(route('settings.workspace.transfer'), ['user_id' => $owner->id])
        ->assertForbidden();
});

it('owner can soft-delete workspace with typed confirmation', function () {
    [$owner, $workspace] = makeWorkspaceWithOwner();

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $workspace->id])
        ->delete(route('settings.workspace.destroy'), ['confirm_name' => $workspace->name])
        ->assertRedirect(route('home'));

    expect(Workspace::withTrashed()->find($workspace->id)->deleted_at)->not->toBeNull();
});

it('workspace delete fails if name does not match', function () {
    [$owner, $workspace] = makeWorkspaceWithOwner();

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $workspace->id])
        ->delete(route('settings.workspace.destroy'), ['confirm_name' => 'wrong name'])
        ->assertRedirect();

    expect(Workspace::find($workspace->id))->not->toBeNull();
});

it('admin cannot delete workspace', function () {
    [$owner, $workspace] = makeWorkspaceWithOwner();
    $admin = User::factory()->create();
    addMember($workspace, $admin, Role::Admin);

    $this->actingAs($admin)
        ->withSession(['workspace_id' => $workspace->id])
        ->delete(route('settings.workspace.destroy'), ['confirm_name' => $workspace->name])
        ->assertForbidden();
});
