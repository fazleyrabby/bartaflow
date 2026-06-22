<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

final class MessagePayload
{
    public function __construct(
        public readonly string $to,
        public readonly string $body,
        public readonly string $type = 'text',
    ) {}
}
