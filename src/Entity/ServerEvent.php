<?php
namespace App\Entity;

use App\Enum\ServerEventType;
use Doctrine\ORM\Mapping as ORM;
use DateTime;
use Exception;
use InvalidArgumentException;

#[ORM\Table(name: 'server_event')]
#[ORM\Index(columns: ['server_id', 'event_type', 'date_created'])]
#[ORM\Index(name: 'server_id_idx', columns: ['server_id'])]
#[ORM\Index(name: 'user_id_idx', columns: ['user_id'])]
#[ORM\Index(name: 'date_created_idx', columns: ['date_created'])]
#[ORM\Entity(repositoryClass: 'App\Repository\ServerEventRepository')]
class ServerEvent
{
    // Keep constants for backward compatibility
    public const TYPE_JOIN = 0;
    public const TYPE_VIEW = 1;
    public const TYPE_BUMP = 2;
    public const TYPES = [
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
     * @throws Exception
     */
    public function __construct()
    {
        $this->dateCreated = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getServer(): Server
    {
        return $this->server;
    }

    public function setServer(Server $server): self
    {
        $this->server = $server;

        return $this;
    }

    public function getEventType(): int
    {
        return $this->eventType;
    }

    public function getEventTypeEnum(): ServerEventType
    {
        return ServerEventType::from($this->eventType);
    }

    public function setEventType(int|ServerEventType $eventType): self
    {
        $value = $eventType instanceof ServerEventType ? $eventType->value : $eventType;

        if (!array_any(ServerEventType::values(), fn($t) => $t === $value)) {
            throw new InvalidArgumentException("Invalid event type {$value}.");
        }
        $this->eventType = $value;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getIp(): string|object
    {
        return $this->ip;
    }

    public function getIpString(): string
    {
        rewind($this->ip);
        return inet_ntop(stream_get_contents($this->ip));
    }

    public function setIp(string|object $ip): self
    {
        $this->ip = $ip;

        return $this;
    }

    public function setIpString(string $ipString): self
    {
        $this->ip = inet_pton($ipString);

        return $this;
    }

    public function getDateCreated(): DateTime
    {
        return $this->dateCreated;
    }

    public function setDateCreated(DateTime $dateCreated): self
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }
}
