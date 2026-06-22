<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function profileUserWithWorkspace(): array
{
    $user = User::factory()->create();
    $ws   = Workspace::factory()->create(['owner_id' => $user->id]);
    WorkspaceUser::create([
        'workspace_id' => $ws->id,
        'user_id'      => $user->id,
        'role'         => Role::Owner->value,
        'status'       => 'active',
        'joined_at'    => now(),
    ]);
    return [$user, $ws];
}

it('shows the profile page for authenticated users', function () {
    [$user, $ws] = profileUserWithWorkspace();

    $this->actingAs($user)
        ->withSession(['workspace_id' => $ws->id])
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('Personal information')
        ->assertSee('Change password');
});

it('updates profile name and phone without changing email', function () {
    [$user, $ws] = profileUserWithWorkspace();
    $user->update(['name' => 'Old Name']);

    $this->actingAs($user)
        ->withSession(['workspace_id' => $ws->id])
        ->patch(route('profile.update'), [
            'name'  => 'New Name',
            'email' => $user->email,
            'phone' => '+8801712345678',
        ])->assertRedirect(route('profile.edit'));

    $user->refresh();
    expect($user->name)->toBe('New Name')
        ->and($user->phone)->toBe('+8801712345678')
        ->and($user->hasVerifiedEmail())->toBeTrue();
});

it('nulls email_verified_at and sends re-verification when email changes', function () {
    [$user, $ws] = profileUserWithWorkspace();

    $this->actingAs($user)
        ->withSession(['workspace_id' => $ws->id])
        ->patch(route('profile.update'), [
            'name'  => $user->name,
            'email' => 'newemail@example.com',
        ])->assertRedirect(route('verification.notice'));

    $user->refresh();
    expect($user->email)->toBe('newemail@example.com')
        ->and($user->hasVerifiedEmail())->toBeFalse();
});

it('rejects email change to an address already in use', function () {
    User::factory()->create(['email' => 'taken@example.com']);
    [$user, $ws] = profileUserWithWorkspace();

    $this->actingAs($user)
        ->withSession(['workspace_id' => $ws->id])
        ->patch(route('profile.update'), [
            'name'  => $user->name,
            'email' => 'taken@example.com',
        ])->assertSessionHasErrors('email');
});

it('updates the password with the correct current password', function () {
    [$user, $ws] = profileUserWithWorkspace();
    $user->update(['password' => bcrypt('OldPass1!')]);

    $this->actingAs($user)
        ->withSession(['workspace_id' => $ws->id])
        ->patch(route('profile.password'), [
            'current_password'      => 'OldPass1!',
            'password'              => 'NewPass2@',
            'password_confirmation' => 'NewPass2@',
        ])->assertRedirect(route('profile.edit'));

    expect(Hash::check('NewPass2@', $user->fresh()->password))->toBeTrue();
});

it('rejects password update when current password is wrong', function () {
    [$user, $ws] = profileUserWithWorkspace();
    $user->update(['password' => bcrypt('correct')]);

    $this->actingAs($user)
        ->withSession(['workspace_id' => $ws->id])
        ->patch(route('profile.password'), [
            'current_password'      => 'wrong',
            'password'              => 'newpassword',
            'password_confirmation' => 'newpassword',
        ])->assertSessionHasErrors('current_password');
});

it('redirects guests away from the profile page', function () {
    $this->get(route('profile.edit'))->assertRedirect(route('login'));
});
