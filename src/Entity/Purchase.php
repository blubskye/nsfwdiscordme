<?php
namespace App\Entity;

use App\Admin\LoggableEntityInterface;
use Doctrine\ORM\Mapping as ORM;
use DateTime;
use Exception;
use InvalidArgumentException;

#[ORM\Table(name: 'purchase')]
#[ORM\Index(columns: ['purchase_token'])]
#[ORM\Entity(repositoryClass: 'App\Repository\PurchaseRepository')]
class Purchase implements LoggableEntityInterface
{
    const STATUS_PENDING = 0;
    const STATUS_SUCCESS = 1;
    const STATUS_FAILURE = 2;

    const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_SUCCESS,
        self::STATUS_FAILURE
    ];

    #[ORM\Id]
    #[ORM\Column(type: 'bigint', options: ['unsigned' => true])]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Server::class)]
    #[ORM\JoinColumn(name: 'server_id', onDelete: 'CASCADE', referencedColumnName: 'id', nullable: false)]
    protected Server $server;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'user_id', onDelete: 'CASCADE', referencedColumnName: 'id')]
    protected User $user;

    #[ORM\Column(type: 'string', length: 32, nullable: true)]
    protected ?string $purchaseToken = null;

    #[ORM\Column(type: 'smallint')]
    protected int $status;

    #[ORM\Column(type: 'smallint')]
    protected int $premiumStatus;

    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    protected int $period;

    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    protected int $price;

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
        $this->status      = self::STATUS_PENDING;
    }

    /**
     * @return string
     */
    public function getLoggableMessage()
    {
        return sprintf(
            'purchase #%d server = "%s", purchase token = "%s"',
            $this->getId(),
            $this->getServer()->getDiscordID(),
            $this->getPurchaseToken()
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
     * @return User
     */
    public function getUser(): User
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
     * @return string|null
     */
    public function getPurchaseToken(): ?string
    {
        return $this->purchaseToken;
    }

    /**
     * @param string $purchaseToken
     *
     * @return self
     */
    public function setPurchaseToken(string $purchaseToken): self
    {
        $this->purchaseToken = $purchaseToken;

        return $this;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @param int $status
     *
     * @return self
     */
    public function setStatus(int $status): self
    {
        if (!in_array($status, self::STATUSES)) {
            throw new InvalidArgumentException(
                "Invalid status ${status}."
            );
        }
        $this->status = $status;

        return $this;
    }

    /**
     * @return int
     */
    public function getPremiumStatus(): int
    {
        return $this->premiumStatus;
    }

    /**
     * @param int $premiumStatus
     *
     * @return self
     */
    public function setPremiumStatus(int $premiumStatus): self
    {
        $this->premiumStatus = $premiumStatus;

        return $this;
    }

    /**
     * @return int
     */
    public function getPeriod(): int
    {
        return $this->period;
    }

    /**
     * @param int $period
     *
     * @return self
     */
    public function setPeriod(int $period): self
    {
        $this->period = $period;

        return $this;
    }

    /**
     * @return int
     */
    public function getPrice(): int
    {
        return $this->price;
    }

    /**
     * @param int $price
     *
     * @return self
     */
    public function setPrice(int $price): self
    {
        $this->price = $price;

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
