<?php
namespace App\Entity;

use App\Admin\LoggableEntityInterface;
use Doctrine\ORM\Mapping as ORM;
use DateTime;
use Exception;
use InvalidArgumentException;

/**
 * @ORM\Table(name="purchase", indexes={@ORM\Index(columns={"purchase_token"})})
 * @ORM\Entity(repositoryClass="App\Repository\PurchaseRepository")
 */
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

    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(type="bigint", options={"unsigned"=true})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Server
     * @ORM\ManyToOne(targetEntity="Server")
     * @ORM\JoinColumn(name="server_id", onDelete="CASCADE", referencedColumnName="id", nullable=false)
     */
    protected $server;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_id", onDelete="CASCADE", referencedColumnName="id")
     */
    protected $user;

    /**
     * @var string
     * @ORM\Column(type="string", length=32, nullable=true)
     */
    protected $purchaseToken;

    /**
     * @var int
     * @ORM\Column(type="smallint")
     */
    protected $status;

    /**
     * @var int
     * @ORM\Column(type="smallint")
     */
    protected $premiumStatus;

    /**
     * @var int
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    protected $period;

    /**
     * @var int
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    protected $price;

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
     * @return Purchase
     */
    public function setServer(Server $server): Purchase
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
     * @return Purchase
     */
    public function setUser(User $user): Purchase
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
     * @return Purchase
     */
    public function setPurchaseToken(string $purchaseToken): Purchase
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
     * @return Purchase
     */
    public function setStatus(int $status): Purchase
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
     * @return Purchase
     */
    public function setPremiumStatus(int $premiumStatus): Purchase
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
     * @return Purchase
     */
    public function setPeriod(int $period): Purchase
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
     * @return Purchase
     */
    public function setPrice(int $price): Purchase
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
     * @return Purchase
     */
    public function setDateCreated(DateTime $dateCreated): Purchase
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }
}
