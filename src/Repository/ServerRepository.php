<?php
namespace App\Repository;

use App\Entity\Server;
use App\Entity\ServerTeamMember;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class GuildRepository
 */
class ServerRepository extends ServiceEntityRepository
{
    /**
     * Constructor
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Server::class);
    }

    /**
     * @param int $id
     *
     * @return object|Server
     */
    public function findByID($id)
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * @param string $discordID
     *
     * @return object|Server
     */
    public function findByDiscordID($discordID)
    {
        return $this->findOneBy(['discordID' => $discordID]);
    }

    /**
     * @param string $slug
     *
     * @return object|Server
     */
    public function findBySlug($slug)
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    /**
     * @param User $user
     *
     * @return Server[]
     */
    public function findByUser(User $user)
    {
        return $this->findBy(['user' => $user, 'isEnabled' => true]);
    }

    /**
     * Returns servers for which the given user is a team member
     *
     * @param User $user
     *
     * @return Server[]
     */
    public function findByTeamMemberUser(User $user)
    {
        return $this->createQueryBuilder('s')
            ->leftJoin(ServerTeamMember::class, 't', Join::WITH, 't.server = s')
            ->where('t.user = :user')
            ->setParameter(':user', $user)
            ->getQuery()
            ->execute();
    }
}
