<?php

declare(strict_types=1);

use App\Enums\MessageStatus;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

/**
 * @return array{0: User, 1: Workspace, 2: WhatsAppAccount, 3: Template}
 */
function logSetup(): array
{
    $owner = User::factory()->create();
    $ws = Workspace::factory()->create(['owner_id' => $owner->id, 'status' => 'active']);
    WorkspaceUser::create([
        'workspace_id' => $ws->id,
        'user_id' => $owner->id,
        'role' => Role::Owner->value,
        'status' => 'active',
        'joined_at' => now(),
    ]);
    $account = WhatsAppAccount::factory()->connected()->default()->create(['workspace_id' => $ws->id]);
    $template = Template::factory()->create(['workspace_id' => $ws->id]);

    return [$owner, $ws, $account, $template];
}

function makeMessage(Workspace $ws, WhatsAppAccount $account, Template $template, array $attrs = []): Message
{
    return Message::factory()->create(array_merge([
        'workspace_id' => $ws->id,
        'whatsapp_account_id' => $account->id,
        'template_id' => $template->id,
    ], $attrs));
}

// ── Listing & filters ───────────────────────────────────────────────────────────

it('lists messages for the current workspace only', function () {
    [$owner, $ws, $account, $template] = logSetup();
    makeMessage($ws, $account, $template, ['recipient_name' => 'Alice']);

    [, $ws2, $account2, $template2] = logSetup();
    makeMessage($ws2, $account2, $template2, ['recipient_name' => 'Bob']);

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->get(route('messages.index'))
        ->assertOk()
        ->assertSee('Alice')
        ->assertDontSee('Bob');
});

it('filters by status', function () {
    [$owner, $ws, $account, $template] = logSetup();
    makeMessage($ws, $account, $template, ['recipient_name' => 'SentOne', 'status' => MessageStatus::Sent]);
    makeMessage($ws, $account, $template, ['recipient_name' => 'FailedOne', 'status' => MessageStatus::Failed]);

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->get(route('messages.index', ['status' => 'failed']))
        ->assertOk()
        ->assertSee('FailedOne')
        ->assertDontSee('SentOne');
});

it('combines status, account and search filters', function () {
    [$owner, $ws, $account, $template] = logSetup();
    $other = WhatsAppAccount::factory()->connected()->create(['workspace_id' => $ws->id]);

    $match = makeMessage($ws, $account, $template, ['recipient_name' => 'Target', 'recipient_phone' => '+8801711111111', 'status' => MessageStatus::Failed]);
    makeMessage($ws, $other, $template, ['recipient_name' => 'WrongAccount', 'status' => MessageStatus::Failed]);
    makeMessage($ws, $account, $template, ['recipient_name' => 'WrongStatus', 'status' => MessageStatus::Sent]);

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->get(route('messages.index', [
            'status' => 'failed',
            'account_id' => $account->id,
            'search' => 'Target',
        ]))
        ->assertOk()
        ->assertSee('Target')
        ->assertDontSee('WrongAccount')
        ->assertDontSee('WrongStatus');

    expect($match->workspace_id)->toBe($ws->id);
});

it('filters by date range on created_at', function () {
    [$owner, $ws, $account, $template] = logSetup();
    makeMessage($ws, $account, $template, ['recipient_name' => 'OldMsg', 'created_at' => now()->subDays(10)]);
    makeMessage($ws, $account, $template, ['recipient_name' => 'NewMsg', 'created_at' => now()]);

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->get(route('messages.index', ['date_from' => now()->subDay()->toDateString()]))
        ->assertOk()
        ->assertSee('NewMsg')
        ->assertDontSee('OldMsg');
});

// ── Detail ──────────────────────────────────────────────────────────────────────

it('shows the message detail with body, variables and timeline', function () {
    [$owner, $ws, $account, $template] = logSetup();
    $message = makeMessage($ws, $account, $template, [
        'body' => 'Hi Alice, your order is ready.',
        'variables_used' => ['name' => 'Alice'],
        'status' => MessageStatus::Sent,
        'provider_message_id' => 'wamid.ABC123',
    ]);

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->get(route('messages.show', $message))
        ->assertOk()
        ->assertSee('Hi Alice, your order is ready.')
        ->assertSee('Alice')
        ->assertSee('wamid.ABC123')
        ->assertSee('Timeline');
});

it('returns 404 viewing another workspace message', function () {
    [$owner1, $ws1] = logSetup();
    [, $ws2, $account2, $template2] = logSetup();
    $foreign = makeMessage($ws2, $account2, $template2);

    $this->actingAs($owner1)
        ->withSession(['workspace_id' => $ws1->id])
        ->get(route('messages.show', $foreign))
        ->assertNotFound();
});

// ── Retry ─────────────────────────────────────────────────────────────────────

it('retries a failed message and re-dispatches the job', function () {
    Queue::fake();
    [$owner, $ws, $account, $template] = logSetup();
    $message = makeMessage($ws, $account, $template, ['status' => MessageStatus::Failed, 'error_message' => 'boom']);

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->post(route('messages.retry', $message))
        ->assertRedirect();

    $message->refresh();
    expect($message->status)->toBe(MessageStatus::Queued)
        ->and($message->error_message)->toBeNull()
        ->and($message->failed_at)->toBeNull();

    Queue::assertPushed(SendMessageJob::class, 1);
});

it('does not retry a non-failed message', function () {
    Queue::fake();
    [$owner, $ws, $account, $template] = logSetup();
    $message = makeMessage($ws, $account, $template, ['status' => MessageStatus::Sent]);

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->post(route('messages.retry', $message))
        ->assertRedirect();

    expect($message->fresh()->status)->toBe(MessageStatus::Sent);
    Queue::assertNothingPushed();
});

it('bulk-retries only failed messages', function () {
    Queue::fake();
    [$owner, $ws, $account, $template] = logSetup();
    $failed1 = makeMessage($ws, $account, $template, ['status' => MessageStatus::Failed]);
    $failed2 = makeMessage($ws, $account, $template, ['status' => MessageStatus::Failed]);
    $sent = makeMessage($ws, $account, $template, ['status' => MessageStatus::Sent]);

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->post(route('messages.retry-bulk'), [
            'message_ids' => [$failed1->id, $failed2->id, $sent->id],
        ])
        ->assertRedirect();

    expect($failed1->fresh()->status)->toBe(MessageStatus::Queued)
        ->and($failed2->fresh()->status)->toBe(MessageStatus::Queued)
        ->and($sent->fresh()->status)->toBe(MessageStatus::Sent);

    Queue::assertPushed(SendMessageJob::class, 2);
});

it('cannot bulk-retry messages from another workspace', function () {
    Queue::fake();
    [$owner1, $ws1] = logSetup();
    [, $ws2, $account2, $template2] = logSetup();
    $foreign = makeMessage($ws2, $account2, $template2, ['status' => MessageStatus::Failed]);

    $this->actingAs($owner1)
        ->withSession(['workspace_id' => $ws1->id])
        ->post(route('messages.retry-bulk'), ['message_ids' => [$foreign->id]])
        ->assertRedirect();

    expect($foreign->fresh()->status)->toBe(MessageStatus::Failed);
    Queue::assertNothingPushed();
});

// ── Performance ───────────────────────────────────────────────────────────────

it('eager-loads relations without N+1', function () {
    [$owner, $ws, $account, $template] = logSetup();
    Contact::factory()->count(5)->create(['workspace_id' => $ws->id])->each(
        fn ($c) => makeMessage($ws, $account, $template, ['contact_id' => $c->id, 'status' => MessageStatus::Sent])
    );

    $queries = 0;
    DB::listen(function () use (&$queries) {
        $queries++;
    });

    $this->actingAs($owner)
        ->withSession(['workspace_id' => $ws->id])
        ->get(route('messages.index'))
        ->assertOk();

    // Bounded query count — eager loading keeps this flat regardless of row count.
    expect($queries)->toBeLessThan(20);
});
