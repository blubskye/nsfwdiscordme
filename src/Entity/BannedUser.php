<?php
namespace App\Entity;

use App\Admin\LoggableEntityInterface;
use Doctrine\ORM\Mapping as ORM;
use DateTime;
use Exception;

#[ORM\Table(name: 'banned_user')]
#[ORM\UniqueConstraint(columns: ['discord_username', 'discord_discriminator'])]
#[ORM\Entity(repositoryClass: 'App\Repository\BannedUserRepository')]
class BannedUser implements LoggableEntityInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(type: 'string', length: 32, nullable: true)]
    protected ?string $discordUsername = null;

    #[ORM\Column(type: 'string', length: 4, nullable: true)]
    protected ?string $discordDiscriminator = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $reason = null;

    #[ORM\Column(type: 'datetime')]
    protected DateTime $dateCreated;

    /**
     * Constructor
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->dateCreated = new DateTime();
    }

    /**
     * @return string
     */
    public function getLoggableMessage()
    {
        return sprintf(
            'banned user #%d "%s#%s"',
            $this->getId(),
            $this->getDiscordUsername(),
            $this->getDiscordDiscriminator()
        );
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getDiscordUsername(): ?string
    {
        return $this->discordUsername;
    }

    /**
     * @param string $discordUsername
     *
     * @return self
     */
    public function setDiscordUsername(string $discordUsername): self
    {
        $this->discordUsername = $discordUsername;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDiscordDiscriminator(): ?string
    {
        return $this->discordDiscriminator;
    }

    /**
     * @param string $discordDiscriminator
     *
     * @return self
     */
    public function setDiscordDiscriminator(string $discordDiscriminator): self
    {
        $this->discordDiscriminator = $discordDiscriminator;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getReason(): ?string
    {
        return $this->reason;
    }

    /**
     * @param string $reason
     *
     * @return self
     */
    public function setReason(string $reason): self
    {
        $this->reason = $reason;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getDateCreated(): DateTime
    {
        return $this->dateCreated;
    }

    /**
     * @param DateTime $dateCreated
     *
     * @return self
     */
    public function setDateCreated(DateTime $dateCreated): self
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }
}
