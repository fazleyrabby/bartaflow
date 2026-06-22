<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceUser;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the landing page', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('BartaFlow');
});

it('renders the dashboard shell for a verified authenticated user', function () {
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
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Dashboard');
});

it('redirects unauthenticated users from the dashboard to login', function () {
    $this->get('/dashboard')
        ->assertRedirect(route('login'));
});
