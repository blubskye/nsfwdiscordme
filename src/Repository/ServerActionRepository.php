<?php
namespace App\Repository;

use App\Entity\ServerAction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class ServerActionRepository
 */
class ServerActionRepository extends ServiceEntityRepository
{
    /**
     * Constructor
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ServerAction::class);
    }

    /**
     * @param $id
     *
     * @return object|ServerAction
     */
    public function findByID($id)
    {
        return $this->findOneBy(['id' => $id]);
    }
}
