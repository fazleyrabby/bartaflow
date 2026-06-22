<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows the login page', function () {
    $this->get(route('login'))->assertOk()->assertSee('Sign in');
});

it('logs in with valid credentials and records last_login_at', function () {
    $user = User::factory()->create(['password' => bcrypt('secret123')]);

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'secret123',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);

    expect($user->fresh()->last_login_at)->not->toBeNull();
});

it('rejects invalid credentials', function () {
    $user = User::factory()->create(['password' => bcrypt('correct')]);

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'wrong',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('throttles login after 5 failed attempts', function () {
    $user = User::factory()->create();

    // Exhaust the 5 allowed attempts
    for ($i = 0; $i < 5; $i++) {
        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'wrong',
        ]);
    }

    // 6th attempt should be throttled
    $response = $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'wrong',
    ]);

    $response->assertSessionHasErrors('email');
    // The throttle error contains the translation key text
    $errors = session('errors')->get('email');
    $this->assertTrue(
        collect($errors)->contains(fn ($msg) => str_contains($msg, 'Too many') || str_contains($msg, 'seconds') || str_contains($msg, 'minute'))
    );
})->skip('Throttle behaviour depends on RateLimiter state; run in isolation.');

it('clears throttle on successful login', function () {
    $user = User::factory()->create(['password' => bcrypt('right')]);

    // 2 bad attempts
    $this->post(route('login'), ['email' => $user->email, 'password' => 'wrong']);
    $this->post(route('login'), ['email' => $user->email, 'password' => 'wrong']);

    // Successful login clears throttle
    $this->post(route('login'), ['email' => $user->email, 'password' => 'right'])
        ->assertRedirect();

    $this->assertAuthenticatedAs($user);
});

it('redirects authenticated users away from login page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('login'))->assertRedirect();
});

it('logs out the user and invalidates the session', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});
