<?php
namespace App\Enum;

enum UserRole: string
{
    case USER = 'ROLE_USER';
    case ADMIN = 'ROLE_ADMIN';
    case SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function fromString(string $role): self
    {
        return match ($role) {
            'ROLE_USER' => self::USER,
            'ROLE_ADMIN' => self::ADMIN,
            'ROLE_SUPER_ADMIN' => self::SUPER_ADMIN,
            default => throw new \InvalidArgumentException("Invalid role: {$role}"),
        };
    }
}
