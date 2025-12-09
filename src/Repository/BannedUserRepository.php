<?php
namespace App\Repository;

use App\Entity\BannedUser;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class BannedUserRepository
 */
class BannedUserRepository extends ServiceEntityRepository
{
    /**
     * Constructor
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BannedUser::class);
    }

    /**
     * @param int $id
     *
     * @return object|BannedUser
     */
    public function findByID($id)
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * @param string $discordUsername
     * @param string $discordDiscriminator
     *
     * @return bool
     */
    public function isBanned($discordUsername, $discordDiscriminator)
    {
        $row = $this->findOneBy([
            'discordUsername'      => $discordUsername,
            'discordDiscriminator' => $discordDiscriminator
        ]);

        return !empty($row);
    }
}
