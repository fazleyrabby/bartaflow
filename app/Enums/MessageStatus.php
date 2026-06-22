<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Lifecycle of an outbound message. See docs/database.md §3.10.
 */
enum MessageStatus: string
{
    case Queued = 'queued';
    case Sending = 'sending';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Read = 'read';
    case Failed = 'failed';
    case Canceled = 'canceled';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Delivered, self::Read, self::Failed, self::Canceled], true);
    }

    public function canRetry(): bool
    {
        return $this === self::Failed;
    }

    /** Tailwind colour token used by the <x-badge> component. */
    public function color(): string
    {
        return match ($this) {
            self::Queued, self::Sending => 'gray',
            self::Sent => 'blue',
            self::Delivered, self::Read => 'green',
            self::Failed => 'red',
            self::Canceled => 'yellow',
        };
    }
}
