<?php

declare(strict_types=1);

use App\Enums\InvitationStatus;
use App\Enums\Role;
use App\Models\Invitation;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceUser;
use App\Notifications\InvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

function wsWithOwner(): array
{
    $owner = User::factory()->create();
    $ws = Workspace::factory()->create(['owner_id' => $owner->id]);
    WorkspaceUser::create([
        'workspace_id' => $ws->id,
        'user_id' => $owner->id,
        'role' => Role::Owner->value,
        'status' => 'active',
        'joined_at' => now(),
    ]);

    return [$owner, $ws];
}

function addWsMember(Workspace $ws, User $user, Role $role): void
{
    WorkspaceUser::create([
        'workspace_id' => $ws->id,
        'user_id' => $user->id,
        'role' => $role->value,
        'status' => 'active',
        'joined_at' => now(),
    ]);
}

// ── Team page ──────────────────────────────────────────────────────────────

it('shows the team page to owner', function () {
    [$owner, $ws] = wsWithOwner();

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->get(route('settings.team'))
        ->assertOk()
        ->assertSee($owner->name);
});

it('shows the team page to admin', function () {
    [$owner, $ws] = wsWithOwner();
    $admin = User::factory()->create();
    addWsMember($ws, $admin, Role::Admin);

    $this->actingAs($admin)
        ->withSession(['workspace_id' => $ws->id])
        ->get(route('settings.team'))
        ->assertOk();
});

it('shows the team page to staff (read-only — invite button hidden)', function () {
    [$owner, $ws] = wsWithOwner();
    $staff = User::factory()->create();
    addWsMember($ws, $staff, Role::Staff);

    $this->actingAs($staff)
        ->withSession(['workspace_id' => $ws->id])
        ->get(route('settings.team'))
        ->assertOk()
        ->assertDontSee('Invite member');
});

// ── Invitations ─────────────────────────────────────────────────────────────

it('owner can invite a new member and notification is queued', function () {
    Notification::fake();
    [$owner, $ws] = wsWithOwner();

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->post(route('settings.team.invite'), [
            'email' => 'newmember@example.com',
            'role' => Role::Staff->value,
        ])
        ->assertRedirect(route('settings.team'));

    $this->assertDatabaseHas('invitations', [
        'workspace_id' => $ws->id,
        'email' => 'newmember@example.com',
        'role' => Role::Staff->value,
        'status' => InvitationStatus::Pending->value,
    ]);

    Notification::assertSentOnDemand(InvitationNotification::class);
});

it('invitation to an existing member is rejected', function () {
    [$owner, $ws] = wsWithOwner();
    $member = User::factory()->create();
    addWsMember($ws, $member, Role::Staff);

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->post(route('settings.team.invite'), [
            'email' => $member->email,
            'role' => Role::Staff->value,
        ])
        ->assertSessionHasErrors('email');
});

it('staff cannot invite', function () {
    [$owner, $ws] = wsWithOwner();
    $staff = User::factory()->create();
    addWsMember($ws, $staff, Role::Staff);

    $this->actingAs($staff)
        ->withSession(['workspace_id' => $ws->id])
        ->post(route('settings.team.invite'), [
            'email' => 'someone@example.com',
            'role' => Role::Staff->value,
        ])
        ->assertForbidden();
});

it('invitation page is publicly visible by token', function () {
    [$owner, $ws] = wsWithOwner();
    $invitation = Invitation::factory()->create([
        'workspace_id' => $ws->id,
        'invited_by' => $owner->id,
    ]);

    $this->get(route('invitations.show', $invitation->token))
        ->assertOk()
        ->assertSee($ws->name);
});

it('expired invitation page redirects to login with error', function () {
    [$owner, $ws] = wsWithOwner();
    $invitation = Invitation::factory()->expired()->create([
        'workspace_id' => $ws->id,
        'invited_by' => $owner->id,
    ]);

    $this->get(route('invitations.show', $invitation->token))
        ->assertRedirect(route('login'));
});

it('authenticated user with matching email can accept invitation', function () {
    [$owner, $ws] = wsWithOwner();
    $invitee = User::factory()->create(['email' => 'invitee@example.com']);
    $invitation = Invitation::factory()->create([
        'workspace_id' => $ws->id,
        'invited_by' => $owner->id,
        'email' => 'invitee@example.com',
        'role' => Role::Admin->value,
    ]);

    $this->actingAs($invitee)
        ->post(route('invitations.accept', $invitation->token))
        ->assertRedirect(route('dashboard'));

    $this->assertDatabaseHas('workspace_users', [
        'workspace_id' => $ws->id,
        'user_id' => $invitee->id,
        'role' => Role::Admin->value,
    ]);

    expect($invitation->fresh()->status)->toBe(InvitationStatus::Accepted);
});

it('invitation acceptance fails when email does not match', function () {
    [$owner, $ws] = wsWithOwner();
    $wrongUser = User::factory()->create(['email' => 'wrong@example.com']);
    $invitation = Invitation::factory()->create([
        'workspace_id' => $ws->id,
        'invited_by' => $owner->id,
        'email' => 'correct@example.com',
    ]);

    $this->actingAs($wrongUser)
        ->post(route('invitations.accept', $invitation->token))
        ->assertSessionHasErrors('email');
});

it('expired invitation cannot be accepted', function () {
    [$owner, $ws] = wsWithOwner();
    $invitee = User::factory()->create(['email' => 'invitee@example.com']);
    $invitation = Invitation::factory()->expired()->create([
        'workspace_id' => $ws->id,
        'invited_by' => $owner->id,
        'email' => 'invitee@example.com',
    ]);

    $this->actingAs($invitee)
        ->post(route('invitations.accept', $invitation->token))
        ->assertSessionHasErrors('token');
});

it('owner can revoke a pending invitation', function () {
    [$owner, $ws] = wsWithOwner();
    $invitation = Invitation::factory()->create([
        'workspace_id' => $ws->id,
        'invited_by' => $owner->id,
    ]);

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->delete(route('settings.invitations.revoke', $invitation))
        ->assertRedirect(route('settings.team'));

    expect($invitation->fresh()->status)->toBe(InvitationStatus::Revoked);
});

// ── Role management ──────────────────────────────────────────────────────────

it('admin can change a staff member\'s role to admin', function () {
    [$owner, $ws] = wsWithOwner();
    $admin = User::factory()->create();
    addWsMember($ws, $admin, Role::Admin);
    $staff = User::factory()->create();
    addWsMember($ws, $staff, Role::Staff);

    $membership = WorkspaceUser::where('workspace_id', $ws->id)
        ->where('user_id', $staff->id)
        ->first();

    $this->actingAs($admin)
        ->withSession(['workspace_id' => $ws->id])
        ->patch(route('settings.team.role', $membership), ['role' => Role::Admin->value])
        ->assertRedirect(route('settings.team'));

    expect($membership->fresh()->role)->toBe(Role::Admin);
});

it('owner role cannot be changed via role update', function () {
    [$owner, $ws] = wsWithOwner();
    $admin = User::factory()->create();
    addWsMember($ws, $admin, Role::Admin);

    $ownerMembership = WorkspaceUser::where('workspace_id', $ws->id)
        ->where('user_id', $owner->id)
        ->first();

    $this->actingAs($admin)
        ->withSession(['workspace_id' => $ws->id])
        ->patch(route('settings.team.role', $ownerMembership), ['role' => Role::Staff->value])
        ->assertForbidden();
});

it('owner cannot be removed from workspace', function () {
    [$owner, $ws] = wsWithOwner();
    $admin = User::factory()->create();
    addWsMember($ws, $admin, Role::Admin);

    $ownerMembership = WorkspaceUser::where('workspace_id', $ws->id)
        ->where('user_id', $owner->id)
        ->first();

    $this->actingAs($admin)
        ->withSession(['workspace_id' => $ws->id])
        ->delete(route('settings.team.remove', $ownerMembership))
        ->assertForbidden();
});

it('admin can remove a staff member', function () {
    [$owner, $ws] = wsWithOwner();
    $admin = User::factory()->create();
    addWsMember($ws, $admin, Role::Admin);
    $staff = User::factory()->create();
    addWsMember($ws, $staff, Role::Staff);

    $membership = WorkspaceUser::where('workspace_id', $ws->id)
        ->where('user_id', $staff->id)
        ->first();

    $this->actingAs($admin)
        ->withSession(['workspace_id' => $ws->id])
        ->delete(route('settings.team.remove', $membership))
        ->assertRedirect(route('settings.team'));

    $this->assertDatabaseMissing('workspace_users', [
        'workspace_id' => $ws->id,
        'user_id' => $staff->id,
    ]);
});
