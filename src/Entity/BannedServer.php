<?php
namespace App\Entity;

use App\Admin\LoggableEntityInterface;
use Doctrine\ORM\Mapping as ORM;
use DateTime;
use Exception;

/**
 * @ORM\Table(name="banned_server",
 *     uniqueConstraints={
 *          @ORM\UniqueConstraint(columns={"discord_id"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\BannedServerRepository")
 */
class BannedServer implements LoggableEntityInterface
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(type="integer", options={"unsigned"=true})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var int
     * @ORM\Column(type="bigint", options={"unsigned"=true})
     */
    protected $discordID;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $reason;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime")
     */
    protected $dateCreated;

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
        return sprintf('banned server #%d "%s"', $this->getId(), $this->getDiscordID());
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return int|null
     */
    public function getDiscordID(): ?int
    {
        return $this->discordID;
    }

    /**
     * @param int $discordID
     *
     * @return BannedServer
     */
    public function setDiscordID(int $discordID): BannedServer
    {
        $this->discordID = $discordID;

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
     * @return BannedServer
     */
    public function setReason(string $reason): BannedServer
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
     * @return BannedServer
     */
    public function setDateCreated(DateTime $dateCreated): BannedServer
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }
}
