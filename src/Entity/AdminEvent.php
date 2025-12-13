<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTime;
use Exception;
use InvalidArgumentException;

#[ORM\Table(name: 'admin_event')]
#[ORM\Index(columns: ['user_id', 'event_type', 'date_created'])]
#[ORM\Entity(repositoryClass: 'App\Repository\AdminEventRepository')]
class AdminEvent
{
    const TYPE_LOGIN         = 0;
    const TYPE_NEW_ENTITY    = 1;
    const TYPE_DELETE_ENTITY = 2;
    const TYPE_UPDATE_ENTITY = 3;

    const TYPES = [
        self::TYPE_LOGIN          => 'login',
        self::TYPE_NEW_ENTITY     => 'create',
        self::TYPE_DELETE_ENTITY  => 'delete',
        self::TYPE_UPDATE_ENTITY  => 'update'
    ];

    #[ORM\Id]
    #[ORM\Column(type: 'bigint', options: ['unsigned' => true])]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', onDelete: 'CASCADE', referencedColumnName: 'id', nullable: true)]
    protected ?User $user = null;

    #[ORM\Column(type: 'smallint')]
    protected int $eventType;

    #[ORM\Column(type: 'string', length: 255)]
    protected string $message;

    #[ORM\Column(type: 'datetime')]
    protected DateTime $dateCreated;

    /**
     * Constructor
     */
    public function __construct()
    {
        try {
            $this->dateCreated = new DateTime();
        } catch (Exception $e) {}
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
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
        if (!array_key_exists($eventType, self::TYPES)) {
            throw new InvalidArgumentException(
                "Invalid event type {$eventType}."
            );
        }
        $this->eventType = $eventType;

        return $this;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param string $message
     *
     * @return self
     */
    public function setMessage(string $message): self
    {
        $this->message = $message;

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
