<?php
namespace App\Twig;

use App\Entity\User;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Class UserExtension
 */
class UserExtension extends AbstractExtension
{
    public const DISCORD_CDN_URL = 'https://cdn.discordapp.com';

    public function getFilters(): array
    {
        return [
            new TwigFilter('avatar', $this->avatar(...)),
            new TwigFilter('displayUsername', $this->displayUsername(...))
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('avatarHash', $this->avatarHash(...))
        ];
    }

    public function avatar(User $user, string $ext = 'png'): string
    {
        $avatarHash = $user->getDiscordAvatar();
        $discordID  = $user->getDiscordID();
        if ($avatarHash && $discordID) {
            return $this->avatarHash($discordID, $avatarHash, $ext);
        }

        return '';
    }

    public function avatarHash(string $discordID, string $avatarHash, string $ext = 'png'): string
    {
        return sprintf('%s/avatars/%d/%s.%s', self::DISCORD_CDN_URL, $discordID, $avatarHash, $ext);
    }

    public function displayUsername(User $user, bool $includeDiscriminator = true): string
    {
        $discordUsername      = $user->getDiscordUsername();
        $discordDiscriminator = $user->getDiscordDiscriminator();

        if (!$includeDiscriminator) {
            return $discordUsername;
        }

        return "{$discordUsername}#{$discordDiscriminator}";
    }
}
