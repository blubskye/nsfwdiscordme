<?php
namespace App\Repository;

use App\Entity\Media;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class MediaRepository
 */
class MediaRepository extends ServiceEntityRepository
{
    /**
     * Constructor
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Media::class);
    }

    /**
     * @param $id
     *
     * @return object|Media
     */
    public function findByID($id)
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * @param $path
     *
     * @return object|Media
     */
    public function findByPath($path)
    {
        return $this->findOneBy(['path' => $path]);
    }
}
