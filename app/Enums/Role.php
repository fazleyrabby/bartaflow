<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Workspace membership roles. See docs/permissions.md §1.
 */
enum Role: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Staff = 'staff';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Admin => 'Admin',
            self::Staff => 'Staff',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Owner => 'emerald',
            self::Admin => 'blue',
            self::Staff => 'gray',
        };
    }

    /**
     * Roles assignable via invitation (Owner is granted only by transfer).
     *
     * @return array<int, self>
     */
    public static function assignable(): array
    {
        return [self::Admin, self::Staff];
    }

    public function isAtLeast(self $role): bool
    {
        return $this->rank() >= $role->rank();
    }

    public function rank(): int
    {
        return match ($this) {
            self::Owner => 3,
            self::Admin => 2,
            self::Staff => 1,
        };
    }
}
