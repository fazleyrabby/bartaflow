<?php

declare(strict_types=1);

namespace App\Enums;

enum TemplateCategory: string
{
    case Order = 'order';
    case Reminder = 'reminder';
    case Invoice = 'invoice';
    case Delivery = 'delivery';
    case General = 'general';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::Order => 'Order',
            self::Reminder => 'Reminder',
            self::Invoice => 'Invoice',
            self::Delivery => 'Delivery',
            self::General => 'General',
            self::Custom => 'Custom',
        };
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
