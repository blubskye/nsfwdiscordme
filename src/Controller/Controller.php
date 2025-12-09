<?php
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

/**
 * Class Controller
 */
class Controller extends AbstractController
{
    const LIMIT = 20;

    const SERVER_ROLE_OWNER   = 'owner';
    const SERVER_ROLE_MANAGER = 'manager';
    const SERVER_ROLE_EDITOR  = 'editor';
    const SERVER_ROLE_NONE    = 'none';

    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var SnowflakeGeneratorInterface
     */
    protected $snowflakeGenerator;

    /**
     * @var PaginatorInterface
     */
    protected $paginator;

    /**
     * @var DiscordService
     */
    protected $discord;

    /**
     * @var ServerAccessInterface
     */
    protected $serverAccess;

    /**
     * Constructor
     *
     * @param DiscordService              $discord
     * @param LoggerInterface             $logger
     * @param EntityManagerInterface      $em
     * @param EventDispatcherInterface    $eventDispatcher
     * @param SnowflakeGeneratorInterface $snowflakeGenerator
     * @param PaginatorInterface          $paginator
     * @param ServerAccessInterface       $serverAccess
     */
    public function __construct(
        DiscordService $discord,
        LoggerInterface $logger,
        EntityManagerInterface $em,
        EventDispatcherInterface $eventDispatcher,
        SnowflakeGeneratorInterface $snowflakeGenerator,
        PaginatorInterface $paginator,
        ServerAccessInterface $serverAccess
    )
    {
        $this->discord            = $discord;
        $this->logger             = $logger;
        $this->em                 = $em;
        $this->eventDispatcher    = $eventDispatcher;
        $this->snowflakeGenerator = $snowflakeGenerator;
        $this->paginator          = $paginator;
        $this->serverAccess       = $serverAccess;
    }

    /**
     * @return User
     */
    public function getUser(): ?User
    {
        return parent::getUser();
    }

    /**
     * @param mixed $query
     * @param int   $limit
     *
     * @return PaginationInterface
     */
    public function paginate($query, $limit = self::LIMIT): PaginationInterface
    {
        return $this->paginator->paginate(
            $query,
            $this->get('request_stack')->getMasterRequest()->query->getInt('page', 1),
            $limit
        );
    }

    /**
     * @param string $slug
     *
     * @return Server
     */
    public function fetchServerOrThrow($slug): Server
    {
        $server = $this->em->getRepository(Server::class)->findBySlug($slug);
        if (!$server || !$server->isEnabled()) {
            throw $this->createNotFoundException();
        }

        return $server;
    }

    /**
     * @param Server $server
     * @param string $role
     * @param User   $user
     *
     * @return bool
     */
    public function hasServerAccess(Server $server, $role = self::SERVER_ROLE_NONE, User $user = null): bool
    {
        return $this->serverAccess->can($server, $role, $user);
    }
}
