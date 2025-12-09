<?php
namespace App\Repository;

use App\Entity\AdminEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class AdminEventRepository
 */
class AdminEventRepository extends ServiceEntityRepository
{
    /**
     * Constructor
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdminEvent::class);
    }

    /**
     * @param int $id
     *
     * @return object|AdminEvent
     */
    public function findByID($id)
    {
        return $this->findOneBy(['id' => $id]);
    }
}
