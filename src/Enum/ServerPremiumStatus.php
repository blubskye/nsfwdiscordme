<?php
namespace App\Enum;

enum ServerPremiumStatus: int
{
    case STANDARD = 0;
    case RUBY = 1;
    case TOPAZ = 2;
    case EMERALD = 3;

    public function label(): string
    {
        return match ($this) {
            self::STANDARD => 'standard',
            self::RUBY => 'ruby',
            self::TOPAZ => 'topaz',
            self::EMERALD => 'emerald',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function fromLabel(string $label): self
    {
        return match ($label) {
            'standard' => self::STANDARD,
            'ruby' => self::RUBY,
            'topaz' => self::TOPAZ,
            'emerald' => self::EMERALD,
            default => throw new \InvalidArgumentException("Invalid status label: {$label}"),
        };
    }
}
