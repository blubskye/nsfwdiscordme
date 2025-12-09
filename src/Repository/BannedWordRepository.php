<?php
namespace App\Repository;

use App\Entity\BannedWord;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class BannedWordRepository
 */
class BannedWordRepository extends ServiceEntityRepository
{
    /**
     * Constructor
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BannedWord::class);
    }

    /**
     * @param int $id
     *
     * @return object|BannedWord
     */
    public function findByID($id)
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * Get all banned words (cached in memory for the request)
     *
     * @return string[]
     */
    private ?array $cachedBannedWords = null;

    private function getBannedWords(): array
    {
        if ($this->cachedBannedWords === null) {
            $this->cachedBannedWords = $this->createQueryBuilder('b')
                ->select('b.word')
                ->getQuery()
                ->getSingleColumnResult();
        }
        return $this->cachedBannedWords;
    }

    /**
     * @param string $text
     *
     * @return bool
     */
    public function containsBannedWords($text)
    {
        $bannedWords = $this->getBannedWords();
        if (empty($bannedWords)) {
            return false;
        }

        // Build a single regex pattern for all banned words
        $patterns = array_map(function($word) {
            return preg_quote($word, '/');
        }, $bannedWords);

        $combinedPattern = '/\b(' . implode('|', $patterns) . ')\b/i';
        return (bool) preg_match($combinedPattern, $text);
    }
}
