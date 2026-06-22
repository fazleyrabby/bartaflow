<?php

declare(strict_types=1);

use App\Enums\AccountStatus;
use App\Enums\MessageStatus;
use App\Jobs\SendMessageJob;
use App\Models\Message;
use App\Models\User;
use App\Models\WhatsAppAccount;
use App\Models\Workspace;
use App\Services\WhatsApp\FakeWhatsAppClient;
use App\Services\WhatsApp\WhatsAppClient;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @return array{0: Message, 1: FakeWhatsAppClient}
 */
function jobMessage(?callable $configureClient = null): array
{
    $owner = User::factory()->create();
    $ws = Workspace::factory()->create(['owner_id' => $owner->id]);
    $account = WhatsAppAccount::factory()->connected()->create(['workspace_id' => $ws->id]);

    $message = Message::factory()->create([
        'workspace_id' => $ws->id,
        'whatsapp_account_id' => $account->id,
        'status' => MessageStatus::Queued,
    ]);

    $client = new FakeWhatsAppClient;
    if ($configureClient) {
        $configureClient($client);
    }
    app()->instance(WhatsAppClient::class, $client);

    return [$message, $client];
}

it('marks the message sent and stores the provider id on success', function () {
    [$message, $client] = jobMessage(fn ($c) => $c->shouldSucceed());

    (new SendMessageJob($message->id))->handle($client);

    $fresh = $message->fresh();
    expect($fresh->status)->toBe(MessageStatus::Sent)
        ->and($fresh->provider_message_id)->not->toBeNull()
        ->and($fresh->attempts)->toBe(1)
        ->and($client->sentMessages())->toHaveCount(1);
});

it('throws on a retryable failure so the queue retries', function () {
    [$message, $client] = jobMessage(fn ($c) => $c->shouldFailRetryable('Network blip.'));

    expect(fn () => (new SendMessageJob($message->id))->handle($client))
        ->toThrow(RuntimeException::class);

    // Left queued for retry, attempt counted.
    $fresh = $message->fresh();
    expect($fresh->status)->toBe(MessageStatus::Queued)
        ->and($fresh->attempts)->toBe(1)
        ->and($fresh->error_code)->toBe('retryable');
});

it('marks failed without retry on a permanent failure', function () {
    [$message, $client] = jobMessage(fn ($c) => $c->shouldFail('Invalid number.'));

    (new SendMessageJob($message->id))->handle($client);

    $fresh = $message->fresh();
    expect($fresh->status)->toBe(MessageStatus::Failed)
        ->and($fresh->error_code)->toBe('permanent')
        ->and($fresh->error_message)->toBe('Invalid number.');
});

it('does not double-send a message that is already sent (idempotency)', function () {
    [$message, $client] = jobMessage(fn ($c) => $c->shouldSucceed());
    $message->update(['status' => MessageStatus::Sent, 'provider_message_id' => 'wamid.existing']);

    (new SendMessageJob($message->id))->handle($client);

    expect($client->sentMessages())->toHaveCount(0)
        ->and($message->fresh()->provider_message_id)->toBe('wamid.existing');
});

it('fails the message when the account is no longer connected', function () {
    [$message, $client] = jobMessage(fn ($c) => $c->shouldSucceed());
    $message->account->update(['status' => AccountStatus::Disconnected->value]);

    (new SendMessageJob($message->id))->handle($client);

    expect($message->fresh()->status)->toBe(MessageStatus::Failed)
        ->and($client->sentMessages())->toHaveCount(0);
});

it('final failure marks the message failed via the failed() handler', function () {
    [$message, $client] = jobMessage();

    (new SendMessageJob($message->id))->failed(new RuntimeException('Gave up.'));

    expect($message->fresh()->status)->toBe(MessageStatus::Failed)
        ->and($message->fresh()->error_message)->toBe('Gave up.');
});
