<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Jobs\SendMessageJob;
use App\Models\Contact;
use App\Models\Message;
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
function msgSetup(bool $verified = true, bool $connected = true, bool $suspended = false): array
{
    $owner = $verified ? User::factory()->create() : User::factory()->unverified()->create();
    $ws = Workspace::factory()->create([
        'owner_id' => $owner->id,
        'status' => $suspended ? 'suspended' : 'active',
    ]);
    WorkspaceUser::create([
        'workspace_id' => $ws->id,
        'user_id' => $owner->id,
        'role' => Role::Owner->value,
        'status' => 'active',
        'joined_at' => now(),
    ]);

    $account = WhatsAppAccount::factory()
        ->when($connected, fn ($f) => $f->connected(), fn ($f) => $f->disconnected())
        ->default()
        ->create(['workspace_id' => $ws->id]);

    $template = Template::factory()->create([
        'workspace_id' => $ws->id,
        'body' => 'Hi {{ name }}, thanks!',
    ]);

    return [$owner, $ws, $account, $template];
}

// ── Queueing ──────────────────────────────────────────────────────────────────

it('queues one message per selected contact and skips opted-out', function () {
    Queue::fake();
    [$owner, $ws, $account, $template] = msgSetup();

    $active = Contact::factory()->count(3)->create(['workspace_id' => $ws->id, 'is_opted_out' => false]);
    $optedOut = Contact::factory()->optedOut()->create(['workspace_id' => $ws->id]);

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->post(route('messages.store'), [
            'account_id' => $account->id,
            'template_id' => $template->id,
            'recipient_mode' => 'selected',
            'contact_ids' => $active->pluck('id')->push($optedOut->id)->all(),
        ])
        ->assertRedirect(route('messages.index'));

    expect(Message::where('workspace_id', $ws->id)->count())->toBe(3);
    Queue::assertPushed(SendMessageJob::class, 3);
});

it('sends to all contacts and reports skipped opted-out count', function () {
    Queue::fake();
    [$owner, $ws, $account, $template] = msgSetup();
    Contact::factory()->count(2)->create(['workspace_id' => $ws->id, 'is_opted_out' => false]);
    Contact::factory()->optedOut()->create(['workspace_id' => $ws->id]);

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->post(route('messages.store'), [
            'account_id' => $account->id,
            'template_id' => $template->id,
            'recipient_mode' => 'all',
        ])
        ->assertRedirect(route('messages.index'))
        ->assertSessionHas('status', '2 messages queued (1 skipped).');

    Queue::assertPushed(SendMessageJob::class, 2);
});

it('skips recipients with unresolvable variables', function () {
    Queue::fake();
    [$owner, $ws, $account, $template] = msgSetup();
    $template->update(['body' => 'Order {{ order_id }} for {{ name }}']);

    // One contact with the custom field, one without.
    $withVar = Contact::factory()->create(['workspace_id' => $ws->id, 'custom_fields' => ['order_id' => 'A1']]);
    $withoutVar = Contact::factory()->create(['workspace_id' => $ws->id, 'custom_fields' => null]);

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->post(route('messages.store'), [
            'account_id' => $account->id,
            'template_id' => $template->id,
            'recipient_mode' => 'selected',
            'contact_ids' => [$withVar->id, $withoutVar->id],
        ])
        ->assertRedirect(route('messages.index'));

    expect(Message::where('workspace_id', $ws->id)->count())->toBe(1);
    Queue::assertPushed(SendMessageJob::class, 1);
});

// ── Block rules ───────────────────────────────────────────────────────────────

it('blocks sending from a disconnected account', function () {
    Queue::fake();
    [$owner, $ws, $account, $template] = msgSetup(connected: false);
    $contact = Contact::factory()->create(['workspace_id' => $ws->id]);

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->post(route('messages.store'), [
            'account_id' => $account->id,
            'template_id' => $template->id,
            'recipient_mode' => 'selected',
            'contact_ids' => [$contact->id],
        ])
        ->assertSessionHas('error');

    expect(Message::count())->toBe(0);
    Queue::assertNothingPushed();
});

it('blocks sending for an unverified user via the verified middleware', function () {
    Queue::fake();
    [$owner, $ws, $account, $template] = msgSetup(verified: false);
    $contact = Contact::factory()->create(['workspace_id' => $ws->id]);

    // The `verified` middleware intercepts before the controller (defense-in-depth
    // is also enforced in the action). The unverified user is redirected away.
    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->post(route('messages.store'), [
            'account_id' => $account->id,
            'template_id' => $template->id,
            'recipient_mode' => 'selected',
            'contact_ids' => [$contact->id],
        ])
        ->assertRedirect(route('verification.notice'));

    expect(Message::count())->toBe(0);
    Queue::assertNothingPushed();
});

it('blocks sending in a suspended workspace', function () {
    Queue::fake();
    [$owner, $ws, $account, $template] = msgSetup(suspended: true);
    $contact = Contact::factory()->create(['workspace_id' => $ws->id]);

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->post(route('messages.store'), [
            'account_id' => $account->id,
            'template_id' => $template->id,
            'recipient_mode' => 'selected',
            'contact_ids' => [$contact->id],
        ])
        ->assertSessionHas('error');

    expect(Message::count())->toBe(0);
});

it('blocks when there are zero valid recipients', function () {
    Queue::fake();
    [$owner, $ws, $account, $template] = msgSetup();
    $optedOut = Contact::factory()->optedOut()->create(['workspace_id' => $ws->id]);

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->post(route('messages.store'), [
            'account_id' => $account->id,
            'template_id' => $template->id,
            'recipient_mode' => 'selected',
            'contact_ids' => [$optedOut->id],
        ])
        ->assertSessionHas('error');

    expect(Message::count())->toBe(0);
});

// ── Tenancy ───────────────────────────────────────────────────────────────────

it('cannot send using another workspace account', function () {
    Queue::fake();
    [$owner1, $ws1, $account1, $template1] = msgSetup();
    [$owner2, $ws2, $account2, $template2] = msgSetup();
    $contact = Contact::factory()->create(['workspace_id' => $ws1->id]);

    $this->actingAs($owner1)
        ->withSession(['workspace_id' => $ws1->id])
        ->post(route('messages.store'), [
            'account_id' => $account2->id, // foreign account
            'template_id' => $template1->id,
            'recipient_mode' => 'selected',
            'contact_ids' => [$contact->id],
        ])
        ->assertSessionHas('error');

    expect(Message::count())->toBe(0);
});

// ── Compose page ──────────────────────────────────────────────────────────────

it('renders the compose page', function () {
    [$owner, $ws, $account, $template] = msgSetup();
    Contact::factory()->create(['workspace_id' => $ws->id]);

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->get(route('messages.create'))
        ->assertOk()
        ->assertSee('Review &amp; send', false);
});
