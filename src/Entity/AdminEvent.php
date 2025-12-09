<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTime;
use Exception;
use InvalidArgumentException;

/**
 * @ORM\Table(name="admin_event",
 *  indexes={
 *      @ORM\Index(columns={"user_id", "event_type", "date_created"})
 *  }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\AdminEventRepository")
 */
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

    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(type="bigint", options={"unsigned"=true})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_id", onDelete="CASCADE", referencedColumnName="id", nullable=true)
     */
    protected $user;

    /**
     * @var int
     * @ORM\Column(type="smallint")
     */
    protected $eventType;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    protected $message;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime")
     */
    protected $dateCreated;

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
     * @return AdminEvent
     */
    public function setUser(User $user): AdminEvent
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
     * @return AdminEvent
     */
    public function setEventType(int $eventType): AdminEvent
    {
        if (!array_key_exists($eventType, self::TYPES)) {
            throw new InvalidArgumentException(
                "Invalid event type ${eventType}."
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
     * @return AdminEvent
     */
    public function setMessage(string $message): AdminEvent
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
     * @return AdminEvent
     */
    public function setDateCreated(DateTime $dateCreated): AdminEvent
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }
}
