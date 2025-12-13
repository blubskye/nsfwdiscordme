<?php
namespace App\Entity;

use App\Admin\LoggableEntityInterface;
use App\Enum\InviteType;
use App\Enum\ServerPremiumStatus;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use DateTime;
use Exception;
use Gedmo\Mapping\Annotation as Gedmo;
use InvalidArgumentException;

#[ORM\Table(name: 'server')]
#[ORM\Index(name: 'enabled_public_idx', columns: ['is_enabled', 'is_public'])]
#[ORM\Index(name: 'enabled_public_premium_idx', columns: ['is_enabled', 'is_public', 'premium_status'])]
#[ORM\Index(name: 'enabled_public_bump_points_idx', columns: ['is_enabled', 'is_public', 'premium_status', 'bump_points', 'date_bumped'])]
#[ORM\Index(name: 'enabled_public_members_online_idx', columns: ['is_enabled', 'is_public', 'members_online', 'bump_points', 'date_bumped'])]
#[ORM\Index(name: 'discord_id_idx', columns: ['discord_id'])]
#[ORM\Index(name: 'user_id_idx', columns: ['user_id'])]
#[ORM\Index(name: 'date_created_idx', columns: ['date_created'])]
#[ORM\Entity(repositoryClass: 'App\Repository\ServerRepository')]
class Server implements LoggableEntityInterface
{
    // Keep constants for backward compatibility
    public const STATUS_STANDARD = 0;
    public const STATUS_RUBY = 1;
    public const STATUS_TOPAZ = 2;
    public const STATUS_EMERALD = 3;
    public const STATUS_STR_STANDARD = 'standard';
    public const STATUS_STR_RUBY = 'ruby';
    public const STATUS_STR_TOPAZ = 'topaz';
    public const STATUS_STR_EMERALD = 'emerald';
    public const STATUSES = [
        self::STATUS_STANDARD,
        self::STATUS_RUBY,
        self::STATUS_TOPAZ,
        self::STATUS_EMERALD
    ];
    public const STATUSES_STR = [
        self::STATUS_STANDARD => self::STATUS_STR_STANDARD,
        self::STATUS_RUBY => self::STATUS_STR_RUBY,
        self::STATUS_TOPAZ => self::STATUS_STR_TOPAZ,
        self::STATUS_EMERALD => self::STATUS_STR_EMERALD
    ];

    public const BUMP_PERIOD_SECONDS = 21600; // 6 hours
    public const POINTS_PER_BUMP = [
        self::STATUS_STANDARD => 1,
        self::STATUS_RUBY => 2,
        self::STATUS_TOPAZ => 3,
        self::STATUS_EMERALD => 4
    ];

    public const INVITE_TYPE_BOT = 'bot';
    public const INVITE_TYPE_WIDGET = 'widget';
    public const INVITE_TYPES = [
        self::INVITE_TYPE_BOT,
        self::INVITE_TYPE_WIDGET
    ];

    #[ORM\Id]
    #[ORM\Column(type: 'bigint', options: ['unsigned' => true])]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(type: 'bigint', options: ['unsigned' => true])]
    protected int $discordID;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'servers')]
    #[ORM\JoinColumn(name: 'user_id', onDelete: 'CASCADE', referencedColumnName: 'id')]
    protected User $user;

    #[ORM\Column(type: 'string', length: 100, unique: true)]
    protected string $slug;

    #[ORM\Column(type: 'string', length: 100)]
    protected string $name;

    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    protected ?string $iconHash = null;

    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    protected ?string $bannerHash = null;

    #[ORM\OneToOne(targetEntity: Media::class, cascade: ['persist'])]
    protected ?Media $iconMedia = null;

    #[ORM\OneToOne(targetEntity: Media::class, cascade: ['persist'])]
    protected ?Media $bannerMedia = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $vanityURL = null;

    #[ORM\Column(type: 'string', length: 160, nullable: true)]
    protected ?string $summary = null;

    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $description = null;

    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    protected int $bumpPoints = 0;

    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    protected int $membersOnline = 0;

    #[ORM\Column(type: 'smallint')]
    protected int $premiumStatus;

    #[ORM\ManyToMany(targetEntity: Category::class, cascade: ['persist'])]
    #[ORM\JoinTable(name: 'server_categories')]
    #[ORM\JoinColumn(name: 'server_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'category_id', referencedColumnName: 'id')]
    protected Collection $categories;

    #[ORM\ManyToMany(targetEntity: Tag::class, cascade: ['persist'])]
    #[ORM\JoinTable(name: 'server_tags')]
    #[ORM\JoinColumn(name: 'server_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'tag_id', referencedColumnName: 'id')]
    protected Collection $tags;

    #[ORM\OneToMany(targetEntity: ServerTeamMember::class, mappedBy: 'server')]
    protected Collection $teamMembers;

    #[ORM\Column(type: 'string', length: 20)]
    protected string $inviteType;

    #[ORM\Column(type: 'bigint', options: ['unsigned' => true], nullable: true)]
    protected ?int $botInviteChannelID = null;

    #[ORM\Column(type: 'boolean')]
    protected bool $botHumanCheck = false;

    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    protected ?string $serverPassword = null;

    #[ORM\Column(type: 'boolean')]
    protected bool $isPublic = true;

    #[ORM\Column(type: 'boolean')]
    protected bool $isActive = true;

    #[ORM\Column(type: 'boolean')]
    protected bool $isEnabled = true;

    #[ORM\Column(type: 'datetime', nullable: true)]
    protected ?DateTime $dateBumped = null;

    #[ORM\Column(type: 'datetime')]
    protected DateTime $dateCreated;

    #[ORM\Column(type: 'datetime')]
    #[Gedmo\Timestampable(on: 'update')]
    protected DateTime $dateUpdated;

    protected int $nextBumpSeconds = 0;

    protected ?ServerEvent $lastBumpEvent = null;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->dateCreated = new DateTime();
        $this->dateUpdated = new DateTime();
        $this->tags = new ArrayCollection();
        $this->categories = new ArrayCollection();
        $this->teamMembers = new ArrayCollection();
        $this->premiumStatus = ServerPremiumStatus::STANDARD->value;
    }

    public function __toString(): string
    {
        return (string) ($this->discordID ?? '');
    }

    public function getLoggableMessage(): string
    {
        return sprintf('server #%d "%s"', $this->getId(), $this->getDiscordID());
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDiscordID(): ?int
    {
        return $this->discordID;
    }

    public function setDiscordID(int $discordID): self
    {
        $this->discordID = $discordID;

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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getIconHash(): ?string
    {
        return $this->iconHash;
    }

    public function setIconHash(string $iconHash): self
    {
        $this->iconHash = $iconHash;

        return $this;
    }

    public function getBannerHash(): ?string
    {
        return $this->bannerHash;
    }

    public function setBannerHash(string $bannerHash): self
    {
        $this->bannerHash = $bannerHash;

        return $this;
    }

    public function getIconMedia(): ?Media
    {
        return $this->iconMedia;
    }

    public function setIconMedia(Media $iconMedia): self
    {
        $this->iconMedia = $iconMedia;

        return $this;
    }

    public function getBannerMedia(): ?Media
    {
        return $this->bannerMedia;
    }

    public function setBannerMedia(Media $bannerMedia): self
    {
        $this->bannerMedia = $bannerMedia;

        return $this;
    }

    public function getVanityURL(): ?string
    {
        return $this->vanityURL;
    }

    public function setVanityURL(string $vanityURL): self
    {
        $this->vanityURL = $vanityURL;

        return $this;
    }

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function setSummary(string $summary): self
    {
        $this->summary = $summary;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getPremiumStatus(): int
    {
        return $this->premiumStatus;
    }

    public function getPremiumStatusEnum(): ServerPremiumStatus
    {
        return ServerPremiumStatus::from($this->premiumStatus);
    }

    public function getPremiumStatusString(): string
    {
        return $this->getPremiumStatusEnum()->label();
    }

    public function setPremiumStatus(int|ServerPremiumStatus $premiumStatus): self
    {
        $value = $premiumStatus instanceof ServerPremiumStatus ? $premiumStatus->value : $premiumStatus;

        if (!array_any(ServerPremiumStatus::values(), fn($s) => $s === $value)) {
            throw new InvalidArgumentException("Invalid premium status {$value}.");
        }
        $this->premiumStatus = $value;

        return $this;
    }

    public function setPremiumStatusString(string $status): self
    {
        $this->setPremiumStatus(ServerPremiumStatus::fromLabel($status));

        return $this;
    }

    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function setCategories(Collection $categories): self
    {
        $this->categories = $categories;

        return $this;
    }

    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function setTags(Collection $tags): self
    {
        $this->tags = $tags;

        return $this;
    }

    public function getBumpPoints(): int
    {
        return $this->bumpPoints;
    }

    public function setBumpPoints(int $bumpPoints): self
    {
        $this->bumpPoints = $bumpPoints;

        return $this;
    }

    public function incrementBumpPoints(int $points = 1): self
    {
        return $this->setBumpPoints($this->getBumpPoints() + $points);
    }

    public function getMembersOnline(): int
    {
        return $this->membersOnline;
    }

    public function setMembersOnline(int $membersOnline): self
    {
        $this->membersOnline = $membersOnline;

        return $this;
    }

    public function getInviteType(): ?string
    {
        return $this->inviteType;
    }

    public function getInviteTypeEnum(): InviteType
    {
        return InviteType::from($this->inviteType);
    }

    public function setInviteType(string|InviteType $inviteType): self
    {
        $value = $inviteType instanceof InviteType ? $inviteType->value : $inviteType;

        if (!array_any(InviteType::values(), fn($t) => $t === $value)) {
            throw new InvalidArgumentException("Invalid invite type {$value}.");
        }
        $this->inviteType = $value;

        return $this;
    }

    public function getBotInviteChannelID(): ?int
    {
        return $this->botInviteChannelID;
    }

    public function setBotInviteChannelID(int $botInviteChannelID): self
    {
        $this->botInviteChannelID = $botInviteChannelID;

        return $this;
    }

    public function isBotHumanCheck(): bool
    {
        return $this->botHumanCheck;
    }

    public function setBotHumanCheck(bool $botHumanCheck): self
    {
        $this->botHumanCheck = $botHumanCheck;

        return $this;
    }

    public function getServerPassword(): ?string
    {
        return $this->serverPassword;
    }

    public function setServerPassword(string $serverPassword): self
    {
        $this->serverPassword = $serverPassword;

        return $this;
    }

    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): self
    {
        $this->isPublic = $isPublic;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function setIsEnabled(bool $isEnabled): self
    {
        $this->isEnabled = $isEnabled;

        return $this;
    }

    public function getTeamMembers(): Collection
    {
        return $this->teamMembers;
    }

    public function setTeamMembers(Collection $teamMembers): self
    {
        $this->teamMembers = $teamMembers;

        return $this;
    }

    public function getDateBumped(): ?DateTime
    {
        return $this->dateBumped;
    }

    public function setDateBumped(DateTime $dateBumped): self
    {
        $this->dateBumped = $dateBumped;

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

    public function getDateUpdated(): DateTime
    {
        return $this->dateUpdated;
    }

    public function setDateUpdated(DateTime $dateUpdated): self
    {
        $this->dateUpdated = $dateUpdated;

        return $this;
    }

    public function getCategory1(): ?Category
    {
        $categories = $this->getCategories();
        return $categories[0] ?? null;
    }

    public function setCategory1(Category $category1): self
    {
        $categories = $this->getCategories();
        $categories[0] = $category1;
        $this->setCategories($categories);

        return $this;
    }

    public function getCategory2(): ?Category
    {
        $categories = $this->getCategories();
        return $categories[1] ?? null;
    }

    public function setCategory2(Category $category2): self
    {
        $categories = $this->getCategories();
        $categories[1] = $category2;
        $this->setCategories($categories);

        return $this;
    }

    public function getNextBumpSeconds(): int
    {
        return $this->nextBumpSeconds;
    }

    public function setNextBumpSeconds(int $nextBumpSeconds): self
    {
        $this->nextBumpSeconds = $nextBumpSeconds;

        return $this;
    }

    public function getLastBumpEvent(): ?ServerEvent
    {
        return $this->lastBumpEvent;
    }

    public function setLastBumpEvent(?ServerEvent $lastBumpEvent): self
    {
        $this->lastBumpEvent = $lastBumpEvent;

        return $this;
    }

    public function isBumpReady(): bool
    {
        $dateBumped = $this->getDateBumped();
        if (!$dateBumped) {
            return true;
        }

        $window = $dateBumped->getTimestamp() + self::BUMP_PERIOD_SECONDS;
        $diff = $window - time();

        return $diff <= 0;
    }

    public function getPointsPerBump(): int
    {
        return self::POINTS_PER_BUMP[$this->getPremiumStatus()];
    }
}
