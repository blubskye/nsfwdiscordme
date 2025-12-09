<?php
namespace App\Repository;

use App\Entity\ServerTeamMember;
use App\Entity\Server;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class ServerTeamMemberRepository
 */
class ServerTeamMemberRepository extends ServiceEntityRepository
{
    /**
     * Constructor
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ServerTeamMember::class);
    }

    /**
     * @param int $id
     *
     * @return object|ServerTeamMember
     */
    public function findByID($id)
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * @param Server $server
     * @param int $id
     *
     * @return object|ServerTeamMember
     */
    public function findByServerAndID(Server $server, $id)
    {
        return $this->findOneBy([
            'server' => $server,
            'id'     => $id
        ]);
    }

    /**
     * @param Server $server
     *
     * @return ServerTeamMember[]
     */
    public function findByServer(Server $server)
    {
        return $this->createQueryBuilder('t')
            ->where('t.server = :server')
            ->setParameter(':server', $server)
            ->orderBy('t.id', 'asc')
            ->getQuery()
            ->execute();
    }

    /**
     * @param User $user
     *
     * @return ServerTeamMember[]
     */
    public function findByUser(User $user)
    {
        return $this->findBy(['user' => $user]);
    }

    /**
     * @param Server $server
     * @param User   $user
     *
     * @return object|ServerTeamMember
     */
    public function findByServerAndUser(Server $server, User $user)
    {
        return $this->findOneBy([
            'server' => $server,
            'user'   => $user
        ]);
    }

    /**
     * @param string $discordUsername
     * @param int    $discordDiscriminator
     *
     * @return object|ServerTeamMember
     */
    public function findByDiscordUsernameAndDiscriminator($discordUsername, $discordDiscriminator)
    {
        return $this->findOneBy([
            'discordUsername'      => $discordUsername,
            'discordDiscriminator' => $discordDiscriminator
        ]);
    }

    /**
     * @param Server $server
     * @param string $discordUsername
     * @param int    $discordDiscriminator
     *
     * @return object|ServerTeamMember
     */
    public function findByServerAndDiscordUsernameAndDiscriminator(Server $server, $discordUsername, $discordDiscriminator)
    {
        return $this->findOneBy([
            'server'               => $server,
            'discordUsername'      => $discordUsername,
            'discordDiscriminator' => $discordDiscriminator
        ]);
    }
}
