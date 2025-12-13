<?php
namespace App\Enum;

enum ServerTeamRole: string
{
    case OWNER = 'owner';
    case MANAGER = 'manager';
    case EDITOR = 'editor';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
