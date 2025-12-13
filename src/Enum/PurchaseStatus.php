<?php
namespace App\Enum;

enum PurchaseStatus: int
{
    case PENDING = 0;
    case SUCCESS = 1;
    case FAILURE = 2;

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
