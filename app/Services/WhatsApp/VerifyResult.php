<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

final class VerifyResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $error = null,
    ) {}

    public static function ok(): self
    {
        return new self(success: true);
    }

    public static function fail(string $error): self
    {
        return new self(success: false, error: $error);
    }
}
