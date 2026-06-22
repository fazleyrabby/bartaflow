<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Models\WhatsAppAccount;
use Illuminate\Support\Str;

final class FakeWhatsAppClient implements WhatsAppClient
{
    private bool $shouldSucceed   = true;
    private ?string $failureError = null;

    public function shouldFail(string $error = 'Simulated failure.'): self
    {
        $this->shouldSucceed = false;
        $this->failureError  = $error;

        return $this;
    }

    public function shouldSucceed(): self
    {
        $this->shouldSucceed = true;
        $this->failureError  = null;

        return $this;
    }

    public function send(WhatsAppAccount $account, MessagePayload $payload): SendResult
    {
        if (! $this->shouldSucceed) {
            return SendResult::fail($this->failureError ?? 'Simulated failure.');
        }

        return SendResult::ok('wamid.fake_' . Str::random(16));
    }

    public function verifyCredentials(WhatsAppAccount $account): VerifyResult
    {
        if (! $this->shouldSucceed) {
            return VerifyResult::fail($this->failureError ?? 'Simulated verification failure.');
        }

        return VerifyResult::ok();
    }
}
