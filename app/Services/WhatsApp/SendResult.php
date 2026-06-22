<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

final class SendResult
{
    public function __construct(
        public readonly bool    $success,
        public readonly ?string $messageId = null,
        public readonly ?string $error     = null,
        public readonly bool    $retryable = false,
    ) {}

    public static function ok(string $messageId): self
    {
        return new self(success: true, messageId: $messageId);
    }

    public static function fail(string $error, bool $retryable = false): self
    {
        return new self(success: false, error: $error, retryable: $retryable);
    }
}
