<?php
namespace App\Repository;

use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class CategoryRepository
 */
class CategoryRepository extends ServiceEntityRepository
{
    /**
     * Constructor
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    /**
     * @param int $id
     *
     * @return object|Category
     */
    public function findByID($id)
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * @param string $slug
     *
     * @return object|Category
     */
    public function findBySlug($slug)
    {
        return $this->findOneBy(['slug' => $slug]);
    }
}
