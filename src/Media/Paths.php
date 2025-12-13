<?php
namespace App\Media;

use RuntimeException;

/**
 * Creates file paths.
 */
class Paths
{
    public function getPathByType(string $type, int|string $serverID, int|string $snowflake, string $extension): string
    {
        return match ($type) {
            'banner' => $this->getBannerPath($serverID, $snowflake, $extension),
            'icon' => $this->getIconPath($serverID, $snowflake, $extension),
            default => throw new RuntimeException("Invalid file type {$type}."),
        };
    }

    public function getIconPath(int|string $serverID, int|string $snowflake, string $extension): string
    {
        return sprintf(
            'icons/%s/%s.%s',
            $serverID,
            $snowflake,
            $extension
        );
    }

    public function getBannerPath(int|string $serverID, int|string $snowflake, string $extension): string
    {
        return sprintf(
            'banners/%s/%s.%s',
            $serverID,
            $snowflake,
            $extension
        );
    }
}
