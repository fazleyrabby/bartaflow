<?php

declare(strict_types=1);

use App\Enums\AccountStatus;
use App\Enums\Role;
use App\Models\User;
use App\Models\WhatsAppAccount;
use App\Models\Workspace;
use App\Models\WorkspaceUser;
use App\Services\WhatsApp\FakeWhatsAppClient;
use App\Services\WhatsApp\WhatsAppClient;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function waWorkspace(): array
{
    $owner = User::factory()->create();
    $ws    = Workspace::factory()->create(['owner_id' => $owner->id]);
    WorkspaceUser::create([
        'workspace_id' => $ws->id,
        'user_id'      => $owner->id,
        'role'         => Role::Owner->value,
        'status'       => 'active',
        'joined_at'    => now(),
    ]);
    return [$owner, $ws];
}

function waAddMember(Workspace $ws, User $user, Role $role): void
{
    WorkspaceUser::create([
        'workspace_id' => $ws->id,
        'user_id'      => $user->id,
        'role'         => $role->value,
        'status'       => 'active',
        'joined_at'    => now(),
    ]);
}

// ── Security: Token encryption ────────────────────────────────────────────────

it('stores the access_token encrypted — plaintext is not in the database', function () {
    [$owner, $ws] = waWorkspace();
    $plainToken = 'super-secret-access-token-' . str_repeat('x', 40);

    $account = WhatsAppAccount::create([
        'workspace_id'        => $ws->id,
        'label'               => 'Test Account',
        'phone_number'        => '+8801700000000',
        'phone_number_id'     => 'phone123',
        'business_account_id' => 'biz123',
        'access_token'        => $plainToken,
        'status'              => AccountStatus::Pending->value,
        'is_default'          => false,
    ]);

    // Value in DB should not match the plaintext token.
    $rawValue = \Illuminate\Support\Facades\DB::table('whatsapp_accounts')
        ->where('id', $account->id)
        ->value('access_token');

    expect($rawValue)->not->toBe($plainToken);
    expect($account->access_token)->toBe($plainToken);
});

// ── Policy: owner/admin mutate; staff reads ──────────────────────────────────

it('staff can view the whatsapp accounts page', function () {
    [$owner, $ws] = waWorkspace();
    $staff = User::factory()->create();
    waAddMember($ws, $staff, Role::Staff);

    $this->actingAs($staff)
        ->withSession(['workspace_id' => $ws->id])
        ->get(route('settings.whatsapp'))
        ->assertOk();
});

it('staff cannot access the connect account page', function () {
    [$owner, $ws] = waWorkspace();
    $staff = User::factory()->create();
    waAddMember($ws, $staff, Role::Staff);

    $this->actingAs($staff)
        ->withSession(['workspace_id' => $ws->id])
        ->get(route('settings.whatsapp.create'))
        ->assertForbidden();
});

it('admin can access the connect account page', function () {
    [$owner, $ws] = waWorkspace();
    $admin = User::factory()->create();
    waAddMember($ws, $admin, Role::Admin);

    $this->actingAs($admin)
        ->withSession(['workspace_id' => $ws->id])
        ->get(route('settings.whatsapp.create'))
        ->assertOk();
});

// ── Connect account ──────────────────────────────────────────────────────────

it('owner can connect a whatsapp account and status becomes connected', function () {
    app()->bind(WhatsAppClient::class, fn () => (new FakeWhatsAppClient())->shouldSucceed());

    [$owner, $ws] = waWorkspace();

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->post(route('settings.whatsapp.store'), [
            'label'               => 'Main Support',
            'phone_number'        => '+8801700000000',
            'phone_number_id'     => 'pid_123',
            'business_account_id' => 'biz_123',
            'access_token'        => str_repeat('a', 64),
        ])
        ->assertRedirect(route('settings.whatsapp'));

    $account = WhatsAppAccount::where('workspace_id', $ws->id)->first();
    expect($account)->not->toBeNull()
        ->and($account->status)->toBe(AccountStatus::Connected)
        ->and($account->is_default)->toBeTrue(); // first account → auto-default
});

it('invalid credentials result in status error', function () {
    app()->bind(WhatsAppClient::class, fn () => (new FakeWhatsAppClient())->shouldFail('Invalid token.'));

    [$owner, $ws] = waWorkspace();

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->post(route('settings.whatsapp.store'), [
            'label'               => 'Bad Account',
            'phone_number'        => '+8801700000001',
            'phone_number_id'     => 'pid_bad',
            'business_account_id' => 'biz_bad',
            'access_token'        => str_repeat('b', 64),
        ])
        ->assertRedirect(route('settings.whatsapp'));

    $account = WhatsAppAccount::where('workspace_id', $ws->id)->first();
    expect($account->status)->toBe(AccountStatus::Error)
        ->and($account->status_reason)->toBe('Invalid token.');
});

// ── Second account does not override default ──────────────────────────────────

it('second connected account does not become default automatically', function () {
    app()->bind(WhatsAppClient::class, fn () => (new FakeWhatsAppClient())->shouldSucceed());

    [$owner, $ws] = waWorkspace();

    // First account — becomes default.
    WhatsAppAccount::factory()->connected()->default()->create(['workspace_id' => $ws->id]);

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->post(route('settings.whatsapp.store'), [
            'label'               => 'Second Account',
            'phone_number'        => '+8801700000002',
            'phone_number_id'     => 'pid_2',
            'business_account_id' => 'biz_2',
            'access_token'        => str_repeat('c', 64),
        ]);

    $accounts = WhatsAppAccount::where('workspace_id', $ws->id)->get();
    expect($accounts->where('is_default', true)->count())->toBe(1);
});

// ── Edit / Update ─────────────────────────────────────────────────────────────

it('admin can update account label', function () {
    [$owner, $ws] = waWorkspace();
    $admin   = User::factory()->create();
    waAddMember($ws, $admin, Role::Admin);
    $account = WhatsAppAccount::factory()->connected()->create(['workspace_id' => $ws->id, 'label' => 'Old Label']);

    $this->actingAs($admin)
        ->withSession(['workspace_id' => $ws->id])
        ->patch(route('settings.whatsapp.update', $account), [
            'label'               => 'New Label',
            'phone_number'        => $account->phone_number,
            'phone_number_id'     => $account->phone_number_id,
            'business_account_id' => $account->business_account_id,
        ])
        ->assertRedirect(route('settings.whatsapp'));

    expect($account->fresh()->label)->toBe('New Label');
});

it('blank token on update keeps the existing token', function () {
    [$owner, $ws] = waWorkspace();
    $originalToken = str_repeat('original', 8);
    $account = WhatsAppAccount::factory()->connected()->create([
        'workspace_id' => $ws->id,
        'access_token' => $originalToken,
    ]);

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->patch(route('settings.whatsapp.update', $account), [
            'label'               => $account->label,
            'phone_number'        => $account->phone_number,
            'phone_number_id'     => $account->phone_number_id,
            'business_account_id' => $account->business_account_id,
            'access_token'        => '',
        ])
        ->assertRedirect(route('settings.whatsapp'));

    expect($account->fresh()->access_token)->toBe($originalToken);
});

// ── Test message ──────────────────────────────────────────────────────────────

it('admin can send a test message via connected account', function () {
    app()->bind(WhatsAppClient::class, fn () => (new FakeWhatsAppClient())->shouldSucceed());

    [$owner, $ws] = waWorkspace();
    $admin   = User::factory()->create();
    waAddMember($ws, $admin, Role::Admin);
    $account = WhatsAppAccount::factory()->connected()->create(['workspace_id' => $ws->id]);

    $this->actingAs($admin)
        ->withSession(['workspace_id' => $ws->id])
        ->postJson(route('settings.whatsapp.test', $account), ['to' => '+8801700000099'])
        ->assertOk()
        ->assertJsonStructure(['message']);
});

it('test message fails when client reports failure', function () {
    app()->bind(WhatsAppClient::class, fn () => (new FakeWhatsAppClient())->shouldFail('API error.'));

    [$owner, $ws] = waWorkspace();
    $account = WhatsAppAccount::factory()->connected()->create(['workspace_id' => $ws->id]);

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->postJson(route('settings.whatsapp.test', $account), ['to' => '+8801700000099'])
        ->assertStatus(422)
        ->assertJsonStructure(['error']);
});

it('disconnected account blocks test message send', function () {
    [$owner, $ws] = waWorkspace();
    $account = WhatsAppAccount::factory()->disconnected()->create(['workspace_id' => $ws->id]);

    $result = app(\App\Actions\WhatsApp\SendTestMessageAction::class)
        ->execute($account, '+8801700000099');

    expect($result->success)->toBeFalse()
        ->and($result->error)->toContain('not connected');
});

// ── Disconnect ────────────────────────────────────────────────────────────────

it('owner can disconnect an account', function () {
    [$owner, $ws] = waWorkspace();
    $account = WhatsAppAccount::factory()->connected()->create(['workspace_id' => $ws->id]);

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->post(route('settings.whatsapp.disconnect', $account))
        ->assertRedirect(route('settings.whatsapp'));

    expect($account->fresh()->status)->toBe(AccountStatus::Disconnected);
});

it('disconnecting the default account promotes the next connected account', function () {
    [$owner, $ws] = waWorkspace();
    $default = WhatsAppAccount::factory()->connected()->default()->create(['workspace_id' => $ws->id]);
    $backup  = WhatsAppAccount::factory()->connected()->create(['workspace_id' => $ws->id]);

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->post(route('settings.whatsapp.disconnect', $default));

    expect($backup->fresh()->is_default)->toBeTrue();
});

// ── Set default ───────────────────────────────────────────────────────────────

it('admin can set a different account as default', function () {
    [$owner, $ws] = waWorkspace();
    $admin   = User::factory()->create();
    waAddMember($ws, $admin, Role::Admin);
    $first  = WhatsAppAccount::factory()->connected()->default()->create(['workspace_id' => $ws->id]);
    $second = WhatsAppAccount::factory()->connected()->create(['workspace_id' => $ws->id]);

    $this->actingAs($admin)
        ->withSession(['workspace_id' => $ws->id])
        ->post(route('settings.whatsapp.default', $second))
        ->assertRedirect(route('settings.whatsapp'));

    expect($second->fresh()->is_default)->toBeTrue()
        ->and($first->fresh()->is_default)->toBeFalse();
});

// ── Tenant isolation ──────────────────────────────────────────────────────────

it('cannot access another workspace\'s account', function () {
    [$owner1, $ws1] = waWorkspace();
    [$owner2, $ws2] = waWorkspace();

    $accountInWs2 = WhatsAppAccount::factory()->connected()->create(['workspace_id' => $ws2->id]);

    $this->actingAs($owner1)
        ->withSession(['workspace_id' => $ws1->id])
        ->get(route('settings.whatsapp.edit', $accountInWs2))
        ->assertNotFound();
});

// ── Health check command ──────────────────────────────────────────────────────

it('health check marks connected accounts with bad creds as error', function () {
    app()->bind(WhatsAppClient::class, fn () => (new FakeWhatsAppClient())->shouldFail('Token revoked.'));

    [$owner, $ws] = waWorkspace();
    $account = WhatsAppAccount::factory()->connected()->create(['workspace_id' => $ws->id]);

    $this->artisan('accounts:health-check')->assertSuccessful();

    expect($account->fresh()->status)->toBe(AccountStatus::Error)
        ->and($account->fresh()->status_reason)->toBe('Token revoked.');
});

it('health check leaves healthy accounts as connected', function () {
    app()->bind(WhatsAppClient::class, fn () => (new FakeWhatsAppClient())->shouldSucceed());

    [$owner, $ws] = waWorkspace();
    $account = WhatsAppAccount::factory()->connected()->create(['workspace_id' => $ws->id]);

    $this->artisan('accounts:health-check')->assertSuccessful();

    expect($account->fresh()->status)->toBe(AccountStatus::Connected);
});
