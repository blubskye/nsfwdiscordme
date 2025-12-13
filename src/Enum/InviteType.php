<?php
namespace App\Enum;

enum InviteType: string
{
    case BOT = 'bot';
    case WIDGET = 'widget';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
