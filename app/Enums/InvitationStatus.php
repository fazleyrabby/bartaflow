<?php

declare(strict_types=1);

namespace App\Enums;

enum InvitationStatus: string
{
    case Pending  = 'pending';
    case Accepted = 'accepted';
    case Expired  = 'expired';
    case Revoked  = 'revoked';

    public function label(): string
    {
        return match ($this) {
            self::Pending  => 'Pending',
            self::Accepted => 'Accepted',
            self::Expired  => 'Expired',
            self::Revoked  => 'Revoked',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending  => 'yellow',
            self::Accepted => 'green',
            self::Expired  => 'gray',
            self::Revoked  => 'red',
        };
    }

    public function isOpen(): bool
    {
        return $this === self::Pending;
    }
}
