<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

it('shows the forgot password page', function () {
    $this->get(route('password.request'))
        ->assertOk()
        ->assertSee('Forgot');
});

it('sends a reset link and does not reveal whether email exists (anti-enumeration)', function () {
    Notification::fake();

    $user = User::factory()->create();

    // Real account → reset email sent
    $this->post(route('password.email'), ['email' => $user->email])
        ->assertRedirect()
        ->assertSessionHas('status');

    Notification::assertSentTo($user, ResetPassword::class);

    // Non-existent account → same redirect, no error leaking existence
    $this->post(route('password.email'), ['email' => 'nobody@example.com'])
        ->assertRedirect()
        ->assertSessionHas('status');
});

it('shows the reset password page', function () {
    $this->get(route('password.reset', ['token' => 'sometoken']))
        ->assertOk()
        ->assertSee('new password');
});

it('resets the password with a valid token', function () {
    Event::fake([PasswordReset::class]);
    Notification::fake();

    $user = User::factory()->create();

    // Generate a real token
    $token = Password::createToken($user);

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'newpassword99',
        'password_confirmation' => 'newpassword99',
    ])->assertRedirect(route('login'));

    expect(Hash::check('newpassword99', $user->fresh()->password))->toBeTrue();
    Event::assertDispatched(PasswordReset::class);
});

it('rejects an invalid or expired reset token', function () {
    $user = User::factory()->create();

    $this->post(route('password.update'), [
        'token' => 'invalid-token',
        'email' => $user->email,
        'password' => 'newpassword99',
        'password_confirmation' => 'newpassword99',
    ])->assertSessionHasErrors('email');
});

it('validates that passwords must match on reset', function () {
    $this->post(route('password.update'), [
        'token' => 'tok',
        'email' => 'user@example.com',
        'password' => 'password123',
        'password_confirmation' => 'different',
    ])->assertSessionHasErrors('password');
});
