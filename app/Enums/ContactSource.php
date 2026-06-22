<?php

declare(strict_types=1);

namespace App\Enums;

enum ContactSource: string
{
    case Manual = 'manual';
    case Import = 'import';
    case Api = 'api';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::Import => 'Imported',
            self::Api => 'API',
        };
    }
}
