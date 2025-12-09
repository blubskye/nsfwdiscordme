<?php
namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class UserRepository
 */
class UserRepository extends ServiceEntityRepository
{
    /**
     * Constructor
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @param int $id
     *
     * @return object|User
     */
    public function findByID($id)
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * @param int $discordID
     *
     * @return object|User
     */
    public function findByDiscordID($discordID)
    {
        return $this->findOneBy(['discordID' => $discordID]);
    }

    /**
     * @param string $discordEmail
     *
     * @return object|User
     */
    public function findByDiscordEmail($discordEmail)
    {
        return $this->findOneBy(['discordEmail' => $discordEmail]);
    }

    /**
     * @param string $discordUsername
     * @param int $discordDiscriminator
     *
     * @return object|User
     */
    public function findByDiscordUsernameAndDiscriminator($discordUsername, $discordDiscriminator)
    {
        return $this->findOneBy([
            'discordUsername' => $discordUsername,
            'discordDiscriminator' => $discordDiscriminator
        ]);
    }
}
