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
     * @param string $text
     *
     * @return bool
     */
    public function containsBannedWords($text)
    {
        /** @var BannedWord[] $banned */
        $banned = $this->findAll();
        foreach($banned as $ban) {
            $word = preg_quote($ban->getWord(), '/');
            if (preg_match("/\b(${word})\b/i", $text)) {
                return true;
            }
        }

        return false;
    }
}
