<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Connection state of a WhatsApp account. See docs/database.md §3.5.
 */
enum AccountStatus: string
{
    case Pending = 'pending';
    case Connected = 'connected';
    case Error = 'error';
    case Disconnected = 'disconnected';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function isConnected(): bool
    {
        return $this === self::Connected;
    }

    /** Whether messages may be sent from an account in this state. */
    public function canSend(): bool
    {
        return $this === self::Connected;
    }

    public function color(): string
    {
        return match ($this) {
            self::Connected => 'green',
            self::Pending => 'blue',
            self::Error => 'red',
            self::Disconnected => 'gray',
        };
    }
}
