<?php
namespace App\Repository;

use App\Entity\Tag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Exception;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class TagRepository
 */
class TagRepository extends ServiceEntityRepository
{
    /**
     * Constructor
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tag::class);
    }

    /**
     * @param $id
     *
     * @return object|Tag
     */
    public function findByID($id)
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * @param string $name
     *
     * @return object|Tag
     */
    public function findByName($name)
    {
        return $this->findOneBy(['name' => $name]);
    }

    /**
     * @param array $strings
     *
     * @return Collection
     * @throws Exception
     */
    public function stringsToTags(array $strings)
    {
        $tags = new ArrayCollection();
        foreach($strings as $string) {
            $string = strtolower($string);
            $tag    = $this->findByName($string);
            if (!$tag) {
                $tag = (new Tag())->setName($string);
            }
            $tags->add($tag);
        }

        return $tags;
    }
}
