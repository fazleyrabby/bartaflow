<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\MessageStatus;
use App\Models\Message;
use App\Services\WhatsApp\MessagePayload;
use App\Services\WhatsApp\WhatsAppClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\SerializesModels;
use RuntimeException;
use Throwable;

class SendMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly int $messageId)
    {
        $this->onQueue('whatsapp');
    }

    /**
     * Exponential-ish backoff between retries (seconds).
     *
     * @return list<int>
     */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    /**
     * Throttle outbound sends to stay within provider limits.
     *
     * @return list<object>
     */
    public function middleware(): array
    {
        return [new RateLimited('whatsapp-send')];
    }

    public function handle(WhatsAppClient $client): void
    {
        $message = Message::find($this->messageId);

        if ($message === null) {
            return;
        }

        // Idempotency guard: never send a message that already succeeded.
        if ($message->status === MessageStatus::Sent) {
            return;
        }

        $account = $message->account;

        if ($account === null || ! $account->isConnected()) {
            $this->markFailed($message, 'account_unavailable', 'WhatsApp account is not connected.');

            return;
        }

        $message->forceFill([
            'status' => MessageStatus::Sending,
            'attempts' => $message->attempts + 1,
        ])->save();

        $result = $client->send($account, new MessagePayload(
            to: $message->recipient_phone,
            body: $message->body,
        ));

        if ($result->success) {
            $message->forceFill([
                'status' => MessageStatus::Sent,
                'provider_message_id' => $result->messageId,
                'error_code' => null,
                'error_message' => null,
                'sent_at' => now(),
            ])->save();

            return;
        }

        if ($result->retryable) {
            // Keep it queued and throw so the queue retries with backoff.
            // The final attempt lands in failed().
            $message->forceFill([
                'status' => MessageStatus::Queued,
                'error_code' => 'retryable',
                'error_message' => $result->error,
            ])->save();

            throw new RuntimeException($result->error ?? 'Retryable send failure.');
        }

        // Permanent failure — no retry.
        $this->markFailed($message, 'permanent', $result->error ?? 'Send failed.');
    }

    /**
     * Called by the queue after all retries are exhausted.
     */
    public function failed(?Throwable $e): void
    {
        $message = Message::find($this->messageId);

        if ($message === null || $message->status === MessageStatus::Sent) {
            return;
        }

        $this->markFailed($message, 'retryable', $e?->getMessage() ?? 'Send failed after retries.');
    }

    private function markFailed(Message $message, string $code, string $reason): void
    {
        $message->forceFill([
            'status' => MessageStatus::Failed,
            'error_code' => $code,
            'error_message' => mb_substr($reason, 0, 255),
            'failed_at' => now(),
        ])->save();
    }
}
