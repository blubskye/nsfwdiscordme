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
     * Find multiple tags by their names in a single query
     *
     * @param array $names
     * @return Tag[]
     */
    public function findByNames(array $names): array
    {
        if (empty($names)) {
            return [];
        }

        return $this->createQueryBuilder('t')
            ->where('t.name IN (:names)')
            ->setParameter('names', array_map('strtolower', $names))
            ->getQuery()
            ->getResult();
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
        if (empty($strings)) {
            return $tags;
        }

        // Normalize all strings to lowercase
        $normalizedStrings = array_map('strtolower', $strings);

        // Fetch all existing tags in a single query
        $existingTags = $this->findByNames($normalizedStrings);

        // Index existing tags by name for O(1) lookup
        $tagsByName = [];
        foreach ($existingTags as $tag) {
            $tagsByName[$tag->getName()] = $tag;
        }

        // Build result collection, creating new tags as needed
        foreach ($normalizedStrings as $string) {
            if (isset($tagsByName[$string])) {
                $tags->add($tagsByName[$string]);
            } else {
                $newTag = (new Tag())->setName($string);
                $tags->add($newTag);
                $tagsByName[$string] = $newTag; // Prevent duplicates
            }
        }

        return $tags;
    }
}
