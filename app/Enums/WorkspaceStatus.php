<?php

declare(strict_types=1);

namespace App\Enums;

enum WorkspaceStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';
    case Deleted = 'deleted';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Suspended => 'Suspended',
            self::Deleted => 'Deleted',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'green',
            self::Suspended => 'yellow',
            self::Deleted => 'red',
        };
    }

    public function isActive(): bool
    {
        return $this === self::Active;
    }

    public function isSuspended(): bool
    {
        return $this === self::Suspended;
    }
}
