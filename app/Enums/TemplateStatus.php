<?php

declare(strict_types=1);

namespace App\Enums;

enum TemplateStatus: string
{
    case Active = 'active';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Archived => 'Archived',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'green',
            self::Archived => 'gray',
        };
    }
}
