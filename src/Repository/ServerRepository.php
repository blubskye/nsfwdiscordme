<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\Server;
use App\Entity\ServerTeamMember;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Server>
 */
class ServerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Server::class);
    }

    public function findByID(int|string $id): ?Server
    {
        return $this->findOneBy(['id' => $id]);
    }

    public function findByDiscordID(string $discordID): ?Server
    {
        return $this->findOneBy(['discordID' => $discordID]);
    }

    public function findBySlug(string $slug): ?Server
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    /**
     * @return array<Server>
     */
    public function findByUser(User $user): array
    {
        return $this->findBy(['user' => $user, 'isEnabled' => true]);
    }

    /**
     * Returns servers for which the given user is a team member.
     * Eager loads categories and tags to avoid N+1 queries.
     *
     * @return array<Server>
     */
    public function findByTeamMemberUser(User $user): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin(ServerTeamMember::class, 't', Join::WITH, 't.server = s')
            ->leftJoin('s.categories', 'c')
            ->leftJoin('s.tags', 'tags')
            ->addSelect('c', 'tags')
            ->where('t.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }
}
