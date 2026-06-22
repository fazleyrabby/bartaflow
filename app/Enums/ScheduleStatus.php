<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Lifecycle of a scheduled message. See docs/database.md §3.11.
 */
enum ScheduleStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Sent = 'sent';
    case Canceled = 'canceled';
    case Failed = 'failed';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Pending, self::Processing], true);
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'blue',
            self::Processing => 'gray',
            self::Sent => 'green',
            self::Canceled => 'yellow',
            self::Failed => 'red',
        };
    }
}
