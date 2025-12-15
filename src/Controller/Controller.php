<?php
declare(strict_types=1);

namespace App\Controller;

use App\Entity\Server;
use App\Entity\User;
use App\Security\ServerAccessInterface;
use App\Services\DiscordService;
use App\Storage\Snowflake\SnowflakeGeneratorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

class Controller extends AbstractController
{
    const LIMIT = 20;

    const SERVER_ROLE_OWNER = 'owner';
    const SERVER_ROLE_MANAGER = 'manager';
    const SERVER_ROLE_EDITOR = 'editor';
    const SERVER_ROLE_NONE = 'none';

    public function __construct(
        protected readonly DiscordService $discord,
        protected readonly LoggerInterface $logger,
        protected readonly EntityManagerInterface $em,
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly SnowflakeGeneratorInterface $snowflakeGenerator,
        protected readonly PaginatorInterface $paginator,
        protected readonly ServerAccessInterface $serverAccess,
        protected readonly RequestStack $requestStack,
        protected readonly RouterInterface $router
    ) {
    }

    public function getUser(): ?User
    {
        return parent::getUser();
    }

    public function paginate(mixed $query, int $limit = self::LIMIT): PaginationInterface
    {
        return $this->paginator->paginate(
            $query,
            $this->requestStack->getMainRequest()->query->getInt('page', 1),
            $limit
        );
    }

    public function fetchServerOrThrow(string $slug): Server
    {
        $server = $this->em->getRepository(Server::class)->findBySlug($slug);
        if (!$server || !$server->isEnabled()) {
            throw $this->createNotFoundException();
        }

        return $server;
    }

    public function hasServerAccess(Server $server, string $role = self::SERVER_ROLE_NONE, ?User $user = null): bool
    {
        return $this->serverAccess->can($server, $role, $user);
    }

    protected function getRouterCollection(): iterable
    {
        return $this->router->getRouteCollection()->all();
    }
}
