<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Models\WhatsAppAccount;
use Illuminate\Support\Str;

final class FakeWhatsAppClient implements WhatsAppClient
{
    private bool $shouldSucceed = true;

    private ?string $failureError = null;

    private bool $failureRetryable = false;

    /** @var list<MessagePayload> */
    private array $sent = [];

    public function shouldFail(string $error = 'Simulated failure.', bool $retryable = false): self
    {
        $this->shouldSucceed = false;
        $this->failureError = $error;
        $this->failureRetryable = $retryable;

        return $this;
    }

    public function shouldFailRetryable(string $error = 'Temporary failure.'): self
    {
        return $this->shouldFail($error, retryable: true);
    }

    public function shouldSucceed(): self
    {
        $this->shouldSucceed = true;
        $this->failureError = null;
        $this->failureRetryable = false;

        return $this;
    }

    /** @return list<MessagePayload> Messages the fake "sent" — for assertions. */
    public function sentMessages(): array
    {
        return $this->sent;
    }

    public function send(WhatsAppAccount $account, MessagePayload $payload): SendResult
    {
        if (! $this->shouldSucceed) {
            return SendResult::fail($this->failureError ?? 'Simulated failure.', retryable: $this->failureRetryable);
        }

        $this->sent[] = $payload;

        return SendResult::ok('wamid.fake_'.Str::random(16));
    }

    public function verifyCredentials(WhatsAppAccount $account): VerifyResult
    {
        if (! $this->shouldSucceed) {
            return VerifyResult::fail($this->failureError ?? 'Simulated verification failure.');
        }

        return VerifyResult::ok();
    }
}
