<?php
namespace App\Repository;

use App\Entity\AccessToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class AccessTokenRepository
 */
class AccessTokenRepository extends ServiceEntityRepository
{
    /**
     * Constructor
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AccessToken::class);
    }

    /**
     * @param $id
     *
     * @return object|AccessToken
     */
    public function findByID($id)
    {
        return $this->findOneBy(['id' => $id]);
    }
}
