<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Models\ActivityLog;
use App\Models\Contact;
use App\Models\Template;
use App\Models\User;
use App\Models\WhatsAppAccount;
use App\Models\Workspace;
use App\Models\WorkspaceUser;
use App\Services\Audit\AuditLogger;
use App\Services\Tenancy\CurrentWorkspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

function auditWorkspace(User $owner, string $status = 'active'): Workspace
{
    $ws = Workspace::factory()->create(['owner_id' => $owner->id, 'status' => $status, 'timezone' => 'Asia/Dhaka']);
    WorkspaceUser::create([
        'workspace_id' => $ws->id, 'user_id' => $owner->id,
        'role' => Role::Owner->value, 'status' => 'active', 'joined_at' => now(),
    ]);

    return $ws;
}

function auditAddMember(Workspace $ws, Role $role = Role::Staff): User
{
    $user = User::factory()->create();
    WorkspaceUser::create([
        'workspace_id' => $ws->id, 'user_id' => $user->id,
        'role' => $role->value, 'status' => 'active', 'joined_at' => now(),
    ]);

    return $user;
}

// ── AuditLogger service ─────────────────────────────────────────────────────────

it('records actor, ip, subject and redacts secrets', function () {
    $owner = User::factory()->create();
    $ws = auditWorkspace($owner);
    $account = WhatsAppAccount::factory()->connected()->create(['workspace_id' => $ws->id]);

    $this->actingAs($owner)->withSession(['workspace_id' => $ws->id]);
    app(CurrentWorkspace::class)->set($ws);

    $log = app(AuditLogger::class)->log('account.connected', $account, 'Connected', [
        'label' => 'Sales',
        'access_token' => 'super-secret-token',
    ]);

    expect($log->workspace_id)->toBe($ws->id)
        ->and($log->user_id)->toBe($owner->id)
        ->and($log->subject_type)->toBe(WhatsAppAccount::class)
        ->and($log->subject_id)->toBe($account->id)
        ->and($log->metadata['access_token'])->toBe('[redacted]')
        ->and($log->metadata['label'])->toBe('Sales');
});

// ── Logging on privileged actions ────────────────────────────────────────────────

it('logs a login', function () {
    $owner = User::factory()->create(['password' => bcrypt('password123')]);
    auditWorkspace($owner);

    $this->post(route('login'), ['email' => $owner->email, 'password' => 'password123']);

    expect(ActivityLog::where('action', 'auth.login')->where('user_id', $owner->id)->exists())->toBeTrue();
});

it('logs a role change', function () {
    $owner = User::factory()->create();
    $ws = auditWorkspace($owner);
    $member = auditAddMember($ws, Role::Staff);
    $membership = WorkspaceUser::where('workspace_id', $ws->id)->where('user_id', $member->id)->first();

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->patch(route('settings.team.role', $membership), ['role' => 'admin'])
        ->assertRedirect();

    $log = ActivityLog::where('action', 'team.role_changed')->first();
    expect($log)->not->toBeNull()
        ->and($log->user_id)->toBe($owner->id)
        ->and($log->metadata['to'])->toBe('admin');
});

it('logs a WhatsApp account connection', function () {
    $owner = User::factory()->create();
    $ws = auditWorkspace($owner);

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->post(route('settings.whatsapp.store'), [
            'label' => 'Main',
            'phone_number' => '+8801712345678',
            'phone_number_id' => '123456',
            'business_account_id' => '789012',
            'access_token' => 'EAAytoken1234567890',
        ])
        ->assertRedirect();

    $log = ActivityLog::where('action', 'account.connected')->first();
    expect($log)->not->toBeNull()
        ->and($log->user_id)->toBe($owner->id);

    // The raw access token must never be persisted in the audit metadata.
    expect(ActivityLog::query()->get()->every(
        fn ($l) => ! str_contains(json_encode($l->metadata) ?: '', 'EAAytoken1234567890')
    ))->toBeTrue();
});

it('logs a message send', function () {
    Queue::fake();
    $owner = User::factory()->create();
    $ws = auditWorkspace($owner);
    $account = WhatsAppAccount::factory()->connected()->default()->create(['workspace_id' => $ws->id]);
    $template = Template::factory()->create(['workspace_id' => $ws->id, 'body' => 'Hi {{ name }}']);
    $contact = Contact::factory()->create(['workspace_id' => $ws->id, 'is_opted_out' => false]);

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->post(route('messages.store'), [
            'account_id' => $account->id,
            'template_id' => $template->id,
            'recipient_mode' => 'selected',
            'contact_ids' => [$contact->id],
        ])
        ->assertRedirect();

    expect(ActivityLog::where('action', 'message.sent')->where('workspace_id', $ws->id)->exists())->toBeTrue();
});

// ── Activity log page ─────────────────────────────────────────────────────────

it('lets an owner view the activity log', function () {
    $owner = User::factory()->create();
    $ws = auditWorkspace($owner);
    ActivityLog::factory()->create(['workspace_id' => $ws->id, 'user_id' => $owner->id, 'action' => 'contact.created', 'description' => 'AuditMarker']);

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->get(route('settings.activity'))
        ->assertOk()
        ->assertSee('AuditMarker');
});

it('forbids staff from the activity log', function () {
    $owner = User::factory()->create();
    $ws = auditWorkspace($owner);
    $staff = auditAddMember($ws, Role::Staff);

    $this->actingAs($staff)
        ->withSession(['workspace_id' => $ws->id])
        ->get(route('settings.activity'))
        ->assertForbidden();
});

it('shows only the current workspace activity', function () {
    $owner = User::factory()->create();
    $ws = auditWorkspace($owner);
    ActivityLog::factory()->create(['workspace_id' => $ws->id, 'user_id' => $owner->id, 'action' => 'contact.created', 'description' => 'MineEntry']);

    $other = User::factory()->create();
    $otherWs = auditWorkspace($other);
    ActivityLog::factory()->create(['workspace_id' => $otherWs->id, 'user_id' => $other->id, 'action' => 'contact.created', 'description' => 'ForeignEntry']);

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->get(route('settings.activity'))
        ->assertOk()
        ->assertSee('MineEntry')
        ->assertDontSee('ForeignEntry');
});
