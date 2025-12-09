<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTime;
use Exception;
use InvalidArgumentException;

/**
 * @ORM\Table(name="server_event",
 *  indexes={
 *      @ORM\Index(columns={"server_id", "event_type", "date_created"})
 *  }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\ServerEventRepository")
 */
class ServerEvent
{
    const TYPE_JOIN = 0;
    const TYPE_VIEW = 1;
    const TYPE_BUMP = 2;

    const TYPES = [
        self::TYPE_JOIN,
        self::TYPE_VIEW,
        self::TYPE_BUMP
    ];

    /**
     * @ORM\Id
     * @ORM\Column(type="bigint", options={"unsigned"=true})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Server
     * @ORM\ManyToOne(targetEntity="Server")
     * @ORM\JoinColumn(name="server_id", onDelete="CASCADE", referencedColumnName="id")
     */
    protected $server;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_id", onDelete="CASCADE", referencedColumnName="id", nullable=true)
     */
    protected $user;

    /**
     * @var string|resource
     * @ORM\Column(type="binary", length=16)
     */
    protected $ip;

    /**
     * @var int
     * @ORM\Column(type="smallint")
     */
    protected $eventType;

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
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Server
     */
    public function getServer(): Server
    {
        return $this->server;
    }

    /**
     * @param Server $server
     *
     * @return ServerEvent
     */
    public function setServer(Server $server): ServerEvent
    {
        $this->server = $server;

        return $this;
    }

    /**
     * @return int
     */
    public function getEventType(): int
    {
        return $this->eventType;
    }

    /**
     * @param int $eventType
     *
     * @return ServerEvent
     */
    public function setEventType(int $eventType): ServerEvent
    {
        if (!in_array($eventType, self::TYPES)) {
            throw new InvalidArgumentException(
                "Invalid event type ${eventType}."
            );
        }
        $this->eventType = $eventType;

        return $this;
    }

    /**
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @param User $user
     *
     * @return ServerEvent
     */
    public function setUser(User $user): ServerEvent
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return resource|string
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @return string
     */
    public function getIpString(): string
    {
        rewind($this->ip);
        return inet_ntop(stream_get_contents($this->ip));
    }

    /**
     * @param resource|string $ip
     *
     * @return ServerEvent
     */
    public function setIp($ip): ServerEvent
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * @param string $ipString
     *
     * @return ServerEvent
     */
    public function setIpString($ipString): ServerEvent
    {
        $this->ip = inet_pton($ipString);

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
     * @return ServerEvent
     */
    public function setDateCreated(DateTime $dateCreated): ServerEvent
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }
}
