<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTime;
use Exception;
use InvalidArgumentException;

#[ORM\Table(name: 'server_event')]
#[ORM\Index(columns: ['server_id', 'event_type', 'date_created'])]
#[ORM\Entity(repositoryClass: 'App\Repository\ServerEventRepository')]
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

    #[ORM\Id]
    #[ORM\Column(type: 'bigint', options: ['unsigned' => true])]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Server::class)]
    #[ORM\JoinColumn(name: 'server_id', onDelete: 'CASCADE', referencedColumnName: 'id')]
    protected Server $server;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', onDelete: 'CASCADE', referencedColumnName: 'id', nullable: true)]
    protected ?User $user = null;

    #[ORM\Column(type: 'binary', length: 16)]
    protected string|object $ip;

    #[ORM\Column(type: 'smallint')]
    protected int $eventType;

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
     * @return self
     */
    public function setServer(Server $server): self
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
     * @return self
     */
    public function setEventType(int $eventType): self
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
     * @return self
     */
    public function setUser(User $user): self
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
     * @return self
     */
    public function setIp($ip): self
    {
        $this->ip = $ip;

        return $this;
    }

    /**
     * @param string $ipString
     *
     * @return self
     */
    public function setIpString($ipString): self
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
     * @return self
     */
    public function setDateCreated(DateTime $dateCreated): self
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }
}
