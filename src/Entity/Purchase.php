<?php
namespace App\Entity;

use App\Admin\LoggableEntityInterface;
use App\Enum\PurchaseStatus;
use Doctrine\ORM\Mapping as ORM;
use DateTime;
use Exception;
use InvalidArgumentException;

#[ORM\Table(name: 'purchase')]
#[ORM\Index(columns: ['purchase_token'])]
#[ORM\Index(name: 'user_id_idx', columns: ['user_id'])]
#[ORM\Index(name: 'server_id_idx', columns: ['server_id'])]
#[ORM\Entity(repositoryClass: 'App\Repository\PurchaseRepository')]
class Purchase implements LoggableEntityInterface
{
    // Keep constants for backward compatibility
    public const STATUS_PENDING = 0;
    public const STATUS_SUCCESS = 1;
    public const STATUS_FAILURE = 2;
    public const STATUSES = [
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
     * @throws Exception
     */
    public function __construct()
    {
        $this->dateCreated = new DateTime();
        $this->status = PurchaseStatus::PENDING->value;
    }

    public function getLoggableMessage(): string
    {
        return sprintf(
            'purchase #%d server = "%s", purchase token = "%s"',
            $this->getId(),
            $this->getServer()->getDiscordID(),
            $this->getPurchaseToken()
        );
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

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getPurchaseToken(): ?string
    {
        return $this->purchaseToken;
    }

    public function setPurchaseToken(string $purchaseToken): self
    {
        $this->purchaseToken = $purchaseToken;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getStatusEnum(): PurchaseStatus
    {
        return PurchaseStatus::from($this->status);
    }

    public function setStatus(int|PurchaseStatus $status): self
    {
        $value = $status instanceof PurchaseStatus ? $status->value : $status;

        if (!array_any(PurchaseStatus::values(), fn($s) => $s === $value)) {
            throw new InvalidArgumentException("Invalid status {$value}.");
        }
        $this->status = $value;

        return $this;
    }

    public function getPremiumStatus(): int
    {
        return $this->premiumStatus;
    }

    public function setPremiumStatus(int $premiumStatus): self
    {
        $this->premiumStatus = $premiumStatus;

        return $this;
    }

    public function getPeriod(): int
    {
        return $this->period;
    }

    public function setPeriod(int $period): self
    {
        $this->period = $period;

        return $this;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function setPrice(int $price): self
    {
        $this->price = $price;

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
