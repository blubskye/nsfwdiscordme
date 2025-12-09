<?php
namespace App\Repository;

use App\Entity\Purchase;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class PurchaseRepository
 */
class PurchaseRepository extends ServiceEntityRepository
{
    /**
     * Constructor
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Purchase::class);
    }

    /**
     * @param int $id
     *
     * @return object|Purchase
     */
    public function findByID($id)
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * @param string $token
     *
     * @return object|Purchase
     */
    public function findByToken($token)
    {
        return $this->findOneBy(['purchaseToken' => $token]);
    }

    /**
     * @param User $user
     *
     * @return Purchase[]
     */
    public function findByUser(User $user)
    {
        return $this->findBy([
            'user' => $user,
            'status' => Purchase::STATUS_SUCCESS
        ]);
    }
}
