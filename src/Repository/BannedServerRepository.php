<?php
namespace App\Repository;

use App\Entity\BannedServer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class BannedServerRepository
 */
class BannedServerRepository extends ServiceEntityRepository
{
    /**
     * Constructor
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BannedServer::class);
    }

    /**
     * @param int $id
     *
     * @return object|BannedServer
     */
    public function findByID($id)
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * @param string|int $discordID
     *
     * @return bool
     */
    public function isBanned($discordID)
    {
        $row = $this->findOneBy(['discordID' => $discordID]);

        return !empty($row);
    }
}
