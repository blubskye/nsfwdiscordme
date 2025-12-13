<?php
namespace App\Twig;

use App\Entity\Server;
use App\Entity\User;
use App\Enum\ServerPremiumStatus;
use App\Security\ServerAccessInterface;
use DateTime;
use Exception;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Class ServerExtension
 */
class ServerExtension extends AbstractExtension
{
    public function __construct(
        protected RouterInterface $router,
        protected ServerAccessInterface $serverAccess
    ) {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('serverURL', $this->serverURL(...)),
            new TwigFilter('serverNextBump', $this->serverNextBump(...)),
            new TwigFilter('premiumStatusString', $this->premiumStatusString(...))
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('hasServerAccess', $this->hasServerAccess(...))
        ];
    }

    public function serverURL(Server $server, int $referenceType = UrlGeneratorInterface::ABSOLUTE_URL): string
    {
        return $this->router->generate('server_index', ['slug' => $server->getSlug()], $referenceType);
    }

    /**
     * @throws Exception
     */
    public function serverNextBump(Server $server): string
    {
        $dateNextBump = $server->getDateNextBump();
        if (!$dateNextBump) {
            return '0';
        }

        $interval = $dateNextBump->diff(new DateTime());
        return match (true) {
            $interval->d !== 0 => $interval->format("%ad %hh %im %ss"),
            $interval->h !== 0 => $interval->format("%hh %im %ss"),
            default => $interval->format("%im %ss"),
        };
    }

    public function hasServerAccess(Server $server, string $role, ?User $user = null): bool
    {
        return $this->serverAccess->can($server, $role, $user);
    }

    public function premiumStatusString(int $status): string
    {
        return ServerPremiumStatus::tryFrom($status)?->label() ?? Server::STATUSES_STR[$status];
    }
}
