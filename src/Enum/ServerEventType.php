<?php
namespace App\Enum;

enum ServerEventType: int
{
    case JOIN = 0;
    case VIEW = 1;
    case BUMP = 2;

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
