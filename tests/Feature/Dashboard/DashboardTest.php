<?php

declare(strict_types=1);

use App\Enums\MessageStatus;
use App\Enums\Role;
use App\Enums\ScheduleStatus;
use App\Models\Contact;
use App\Models\Message;
use App\Models\ScheduledMessage;
use App\Models\Template;
use App\Models\User;
use App\Models\WhatsAppAccount;
use App\Models\Workspace;
use App\Models\WorkspaceUser;
use App\Services\Dashboard\DashboardMetrics;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @return array{0: User, 1: Workspace}
 */
function dashSetup(): array
{
    $owner = User::factory()->create();
    $ws = Workspace::factory()->create(['owner_id' => $owner->id, 'status' => 'active', 'timezone' => 'Asia/Dhaka']);
    WorkspaceUser::create([
        'workspace_id' => $ws->id,
        'user_id' => $owner->id,
        'role' => Role::Owner->value,
        'status' => 'active',
        'joined_at' => now(),
    ]);

    return [$owner, $ws];
}

it('renders the dashboard', function () {
    [$owner, $ws] = dashSetup();

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Dashboard');
});

// ── KPIs ─────────────────────────────────────────────────────────────────────

it('computes accurate KPI counts scoped to the workspace', function () {
    [, $ws] = dashSetup();
    $account = WhatsAppAccount::factory()->connected()->create(['workspace_id' => $ws->id]);
    $template = Template::factory()->create(['workspace_id' => $ws->id]);

    Message::factory()->count(2)->create([
        'workspace_id' => $ws->id, 'whatsapp_account_id' => $account->id,
        'status' => MessageStatus::Sent, 'sent_at' => now(),
    ]);
    Message::factory()->create([
        'workspace_id' => $ws->id, 'whatsapp_account_id' => $account->id,
        'status' => MessageStatus::Failed, 'failed_at' => now()->subHours(2),
    ]);
    // A failed message older than 24h must NOT count.
    Message::factory()->create([
        'workspace_id' => $ws->id, 'whatsapp_account_id' => $account->id,
        'status' => MessageStatus::Failed, 'failed_at' => now()->subDays(3),
    ]);
    Contact::factory()->count(4)->create(['workspace_id' => $ws->id]);
    ScheduledMessage::factory()->create([
        'workspace_id' => $ws->id, 'whatsapp_account_id' => $account->id, 'template_id' => $template->id,
        'status' => ScheduleStatus::Pending, 'run_at' => now()->addDay(),
    ]);

    // Foreign workspace noise.
    [, $other] = dashSetup();
    $otherAccount = WhatsAppAccount::factory()->connected()->create(['workspace_id' => $other->id]);
    Message::factory()->count(5)->create([
        'workspace_id' => $other->id, 'whatsapp_account_id' => $otherAccount->id,
        'status' => MessageStatus::Sent, 'sent_at' => now(),
    ]);

    $kpis = app(DashboardMetrics::class)->kpis($ws->fresh());

    expect($kpis['sent_today'])->toBe(2)
        ->and($kpis['failed_24h'])->toBe(1)
        ->and($kpis['total_contacts'])->toBe(4)
        ->and($kpis['active_templates'])->toBe(1)
        ->and($kpis['scheduled_upcoming'])->toBe(1)
        ->and($kpis['connected_accounts'])->toBe(1);
});

// ── Checklist ─────────────────────────────────────────────────────────────────

it('reflects an empty workspace as an incomplete checklist', function () {
    [, $ws] = dashSetup();

    $checklist = app(DashboardMetrics::class)->checklist($ws);

    expect($checklist['complete'])->toBeFalse()
        ->and(collect($checklist['steps'])->every(fn ($s) => $s['done'] === false))->toBeTrue();
});

it('marks the checklist complete once every step is done', function () {
    [, $ws] = dashSetup();
    $account = WhatsAppAccount::factory()->connected()->create(['workspace_id' => $ws->id]);
    Contact::factory()->create(['workspace_id' => $ws->id]);
    Template::factory()->create(['workspace_id' => $ws->id]);
    Message::factory()->create([
        'workspace_id' => $ws->id, 'whatsapp_account_id' => $account->id, 'status' => MessageStatus::Sent, 'sent_at' => now(),
    ]);

    $checklist = app(DashboardMetrics::class)->checklist($ws);

    expect($checklist['complete'])->toBeTrue();
});

// ── Cache ─────────────────────────────────────────────────────────────────────

it('caches KPI aggregates and invalidates on message writes', function () {
    [, $ws] = dashSetup();
    $account = WhatsAppAccount::factory()->connected()->create(['workspace_id' => $ws->id]);
    $metrics = app(DashboardMetrics::class);

    expect($metrics->kpis($ws)['sent_today'])->toBe(0);

    // Direct insert without firing the observer — proves the cache is being served.
    Message::withoutEvents(fn () => Message::factory()->create([
        'workspace_id' => $ws->id, 'whatsapp_account_id' => $account->id, 'status' => MessageStatus::Sent, 'sent_at' => now(),
    ]));
    expect($metrics->kpis($ws)['sent_today'])->toBe(0); // still cached

    // A normal write fires the observer, which forgets the cache.
    Message::factory()->create([
        'workspace_id' => $ws->id, 'whatsapp_account_id' => $account->id, 'status' => MessageStatus::Sent, 'sent_at' => now(),
    ]);
    expect($metrics->kpis($ws)['sent_today'])->toBe(2);
});

// ── Tenancy ───────────────────────────────────────────────────────────────────

it('shows upcoming schedules only for the current workspace', function () {
    [$owner, $ws] = dashSetup();
    $account = WhatsAppAccount::factory()->connected()->create(['workspace_id' => $ws->id]);
    $template = Template::factory()->create(['workspace_id' => $ws->id, 'name' => 'MyPromo']);
    ScheduledMessage::factory()->create([
        'workspace_id' => $ws->id, 'whatsapp_account_id' => $account->id, 'template_id' => $template->id,
        'name' => 'MyPromo', 'status' => ScheduleStatus::Pending, 'run_at' => now()->addDay(),
    ]);

    [, $other] = dashSetup();
    $oa = WhatsAppAccount::factory()->connected()->create(['workspace_id' => $other->id]);
    $ot = Template::factory()->create(['workspace_id' => $other->id]);
    ScheduledMessage::factory()->create([
        'workspace_id' => $other->id, 'whatsapp_account_id' => $oa->id, 'template_id' => $ot->id,
        'name' => 'ForeignPromo', 'status' => ScheduleStatus::Pending, 'run_at' => now()->addDay(),
    ]);

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('MyPromo')
        ->assertDontSee('ForeignPromo');
});
