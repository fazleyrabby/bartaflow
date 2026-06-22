<?php

declare(strict_types=1);

use App\Actions\Scheduling\DispatchDueSchedulesAction;
use App\Enums\Role;
use App\Enums\ScheduleStatus;
use App\Jobs\SendMessageJob;
use App\Models\Contact;
use App\Models\Message;
use App\Models\ScheduledMessage;
use App\Models\Template;
use App\Models\User;
use App\Models\WhatsAppAccount;
use App\Models\Workspace;
use App\Models\WorkspaceUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

/**
 * @return array{0: User, 1: Workspace, 2: WhatsAppAccount, 3: Template}
 */
function schedSetup(): array
{
    $owner = User::factory()->create();
    $ws = Workspace::factory()->create(['owner_id' => $owner->id, 'timezone' => 'Asia/Dhaka', 'status' => 'active']);
    WorkspaceUser::create([
        'workspace_id' => $ws->id,
        'user_id' => $owner->id,
        'role' => Role::Owner->value,
        'status' => 'active',
        'joined_at' => now(),
    ]);
    $account = WhatsAppAccount::factory()->connected()->default()->create(['workspace_id' => $ws->id]);
    $template = Template::factory()->create(['workspace_id' => $ws->id, 'body' => 'Hi {{ name }}!']);

    return [$owner, $ws, $account, $template];
}

// ── Create & timezone ─────────────────────────────────────────────────────────

it('stores run_at in UTC converted from the workspace timezone', function () {
    [$owner, $ws, $account, $template] = schedSetup();
    $contact = Contact::factory()->create(['workspace_id' => $ws->id]);

    // 12:00 in Asia/Dhaka (+06:00) → 06:00 UTC.
    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->post(route('scheduling.store'), [
            'account_id' => $account->id,
            'template_id' => $template->id,
            'recipient_mode' => 'selected',
            'contact_ids' => [$contact->id],
            'run_at' => '2099-12-01T12:00',
        ])
        ->assertRedirect(route('scheduling.index'));

    $schedule = ScheduledMessage::where('workspace_id', $ws->id)->first();
    expect($schedule)->not->toBeNull()
        ->and($schedule->run_at->utc()->format('Y-m-d H:i'))->toBe('2099-12-01 06:00')
        ->and($schedule->status)->toBe(ScheduleStatus::Pending);
});

it('rejects a run_at in the past', function () {
    [$owner, $ws, $account, $template] = schedSetup();
    $contact = Contact::factory()->create(['workspace_id' => $ws->id]);

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->post(route('scheduling.store'), [
            'account_id' => $account->id,
            'template_id' => $template->id,
            'recipient_mode' => 'selected',
            'contact_ids' => [$contact->id],
            'run_at' => '2000-01-01T09:00',
        ])
        ->assertSessionHasErrors('run_at');

    expect(ScheduledMessage::count())->toBe(0);
});

// ── Dispatch ──────────────────────────────────────────────────────────────────

it('dispatches a due schedule and links created messages', function () {
    Queue::fake();
    [$owner, $ws, $account, $template] = schedSetup();
    $contacts = Contact::factory()->count(2)->create(['workspace_id' => $ws->id, 'is_opted_out' => false]);

    $schedule = ScheduledMessage::factory()->due()->create([
        'workspace_id' => $ws->id,
        'whatsapp_account_id' => $account->id,
        'template_id' => $template->id,
        'created_by' => $owner->id,
        'recipient_type' => 'contacts',
        'recipient_payload' => ['contact_ids' => $contacts->pluck('id')->all()],
    ]);

    $result = app(DispatchDueSchedulesAction::class)->execute();

    expect($result['dispatched'])->toBe(1)
        ->and($schedule->fresh()->status)->toBe(ScheduleStatus::Sent)
        ->and(Message::where('scheduled_message_id', $schedule->id)->count())->toBe(2);

    Queue::assertPushed(SendMessageJob::class, 2);
});

it('resolves recipients at run time and skips opted-out', function () {
    Queue::fake();
    [$owner, $ws, $account, $template] = schedSetup();
    $active = Contact::factory()->create(['workspace_id' => $ws->id, 'is_opted_out' => false]);
    $optedOut = Contact::factory()->create(['workspace_id' => $ws->id, 'is_opted_out' => false]);

    $schedule = ScheduledMessage::factory()->due()->create([
        'workspace_id' => $ws->id,
        'whatsapp_account_id' => $account->id,
        'template_id' => $template->id,
        'created_by' => $owner->id,
        'recipient_payload' => ['contact_ids' => [$active->id, $optedOut->id]],
    ]);

    // Contact opts out after scheduling but before run.
    $optedOut->update(['is_opted_out' => true]);

    app(DispatchDueSchedulesAction::class)->execute();

    expect(Message::where('scheduled_message_id', $schedule->id)->count())->toBe(1);
});

it('does not dispatch a canceled schedule', function () {
    Queue::fake();
    [$owner, $ws, $account, $template] = schedSetup();
    $contact = Contact::factory()->create(['workspace_id' => $ws->id]);

    ScheduledMessage::factory()->due()->canceled()->create([
        'workspace_id' => $ws->id,
        'whatsapp_account_id' => $account->id,
        'template_id' => $template->id,
        'created_by' => $owner->id,
        'recipient_payload' => ['contact_ids' => [$contact->id]],
    ]);

    $result = app(DispatchDueSchedulesAction::class)->execute();

    expect($result['dispatched'])->toBe(0)
        ->and(Message::count())->toBe(0);
    Queue::assertNothingPushed();
});

it('does not double-dispatch across concurrent runs', function () {
    Queue::fake();
    [$owner, $ws, $account, $template] = schedSetup();
    $contact = Contact::factory()->create(['workspace_id' => $ws->id]);

    ScheduledMessage::factory()->due()->create([
        'workspace_id' => $ws->id,
        'whatsapp_account_id' => $account->id,
        'template_id' => $template->id,
        'created_by' => $owner->id,
        'recipient_payload' => ['contact_ids' => [$contact->id]],
    ]);

    app(DispatchDueSchedulesAction::class)->execute();
    app(DispatchDueSchedulesAction::class)->execute(); // second run finds nothing pending

    expect(Message::count())->toBe(1);
    Queue::assertPushed(SendMessageJob::class, 1);
});

it('fails a schedule that is overdue beyond the grace window', function () {
    Queue::fake();
    [$owner, $ws, $account, $template] = schedSetup();
    $contact = Contact::factory()->create(['workspace_id' => $ws->id]);

    $schedule = ScheduledMessage::factory()->create([
        'workspace_id' => $ws->id,
        'whatsapp_account_id' => $account->id,
        'template_id' => $template->id,
        'created_by' => $owner->id,
        'status' => ScheduleStatus::Pending,
        'run_at' => now()->subHours(48), // way beyond the 24h grace
        'recipient_payload' => ['contact_ids' => [$contact->id]],
    ]);

    $result = app(DispatchDueSchedulesAction::class)->execute();

    expect($result['skipped'])->toBe(1)
        ->and($schedule->fresh()->status)->toBe(ScheduleStatus::Failed)
        ->and(Message::count())->toBe(0);
});

it('runs the schedule:dispatch-due command', function () {
    Queue::fake();
    [$owner, $ws, $account, $template] = schedSetup();
    $contact = Contact::factory()->create(['workspace_id' => $ws->id]);

    $schedule = ScheduledMessage::factory()->due()->create([
        'workspace_id' => $ws->id,
        'whatsapp_account_id' => $account->id,
        'template_id' => $template->id,
        'created_by' => $owner->id,
        'recipient_payload' => ['contact_ids' => [$contact->id]],
    ]);

    $this->artisan('schedule:dispatch-due')->assertSuccessful();

    expect($schedule->fresh()->status)->toBe(ScheduleStatus::Sent);
});

// ── Cancel ────────────────────────────────────────────────────────────────────

it('cancels a pending schedule', function () {
    [$owner, $ws, $account, $template] = schedSetup();
    $schedule = ScheduledMessage::factory()->create([
        'workspace_id' => $ws->id,
        'whatsapp_account_id' => $account->id,
        'template_id' => $template->id,
    ]);

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->post(route('scheduling.cancel', $schedule))
        ->assertRedirect(route('scheduling.index'));

    expect($schedule->fresh()->status)->toBe(ScheduleStatus::Canceled);
});

// ── Tenancy ───────────────────────────────────────────────────────────────────

it('returns 404 when editing another workspace schedule', function () {
    [$owner1, $ws1] = schedSetup();
    [$owner2, $ws2, $account2, $template2] = schedSetup();
    $foreign = ScheduledMessage::factory()->create([
        'workspace_id' => $ws2->id,
        'whatsapp_account_id' => $account2->id,
        'template_id' => $template2->id,
    ]);

    $this->actingAs($owner1)
        ->withSession(['workspace_id' => $ws1->id])
        ->get(route('scheduling.edit', $foreign))
        ->assertNotFound();
});

// ── Page render ───────────────────────────────────────────────────────────────

it('renders the scheduling create page', function () {
    [$owner, $ws] = schedSetup();
    Contact::factory()->create(['workspace_id' => $ws->id]);

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->get(route('scheduling.create'))
        ->assertOk()
        ->assertSee('When to send');
});
