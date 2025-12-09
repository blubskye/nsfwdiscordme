<?php
namespace App\Entity;

use App\Admin\LoggableEntityInterface;
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
    const STATUS_STANDARD     = 0;
    const STATUS_RUBY         = 1;
    const STATUS_TOPAZ        = 2;
    const STATUS_EMERALD      = 3;
    const STATUS_STR_STANDARD = 'standard';
    const STATUS_STR_RUBY     = 'ruby';
    const STATUS_STR_TOPAZ    = 'topaz';
    const STATUS_STR_EMERALD  = 'emerald';
    const STATUSES            = [
        self::STATUS_STANDARD,
        self::STATUS_RUBY,
        self::STATUS_TOPAZ,
        self::STATUS_EMERALD
    ];
    const STATUSES_STR = [
        self::STATUS_STANDARD => self::STATUS_STR_STANDARD,
        self::STATUS_RUBY     => self::STATUS_STR_RUBY,
        self::STATUS_TOPAZ    => self::STATUS_STR_TOPAZ,
        self::STATUS_EMERALD  => self::STATUS_STR_EMERALD
    ];

    const BUMP_PERIOD_SECONDS = 21600; // 6 hours
    const POINTS_PER_BUMP     = [
        self::STATUS_STANDARD => 1,
        self::STATUS_RUBY     => 2,
        self::STATUS_TOPAZ    => 3,
        self::STATUS_EMERALD  => 4
    ];

    const INVITE_TYPE_BOT    = 'bot';
    const INVITE_TYPE_WIDGET = 'widget';
    const INVITE_TYPES       = [
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
     * Constructor
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->dateCreated   = new DateTime();
        $this->dateUpdated   = new DateTime();
        $this->tags          = new ArrayCollection();
        $this->categories    = new ArrayCollection();
        $this->teamMembers   = new ArrayCollection();
        $this->premiumStatus = self::STATUS_STANDARD;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return (string)($this->discordID ?? '');
    }

    /**
     * @return string
     */
    public function getLoggableMessage()
    {
        return sprintf('server #%d "%s"', $this->getId(), $this->getDiscordID());
    }

    /**
     * @return mixed
     */
    public function getId()
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
     * @return self
     */
    public function setDiscordID($discordID): self
    {
        $this->discordID = $discordID;

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
    public function getSlug(): ?string
    {
        return $this->slug;
    }

    /**
     * @param string $slug
     *
     * @return self
     */
    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return self
     */
    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getIconHash(): ?string
    {
        return $this->iconHash;
    }

    /**
     * @param string $iconHash
     *
     * @return self
     */
    public function setIconHash(string $iconHash): self
    {
        $this->iconHash = $iconHash;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getBannerHash(): ?string
    {
        return $this->bannerHash;
    }

    /**
     * @param string $bannerHash
     *
     * @return self
     */
    public function setBannerHash(string $bannerHash): self
    {
        $this->bannerHash = $bannerHash;

        return $this;
    }

    /**
     * @return Media|null
     */
    public function getIconMedia(): ?Media
    {
        return $this->iconMedia;
    }

    /**
     * @param Media $iconMedia
     *
     * @return self
     */
    public function setIconMedia(Media $iconMedia): self
    {
        $this->iconMedia = $iconMedia;

        return $this;
    }

    /**
     * @return Media|null
     */
    public function getBannerMedia(): ?Media
    {
        return $this->bannerMedia;
    }

    /**
     * @param Media $bannerMedia
     *
     * @return self
     */
    public function setBannerMedia(Media $bannerMedia): self
    {
        $this->bannerMedia = $bannerMedia;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getVanityURL(): ?string
    {
        return $this->vanityURL;
    }

    /**
     * @param string $vanityURL
     *
     * @return self
     */
    public function setVanityURL(string $vanityURL): self
    {
        $this->vanityURL = $vanityURL;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSummary(): ?string
    {
        return $this->summary;
    }

    /**
     * @param string $summary
     *
     * @return self
     */
    public function setSummary(string $summary): self
    {
        $this->summary = $summary;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return self
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;

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
     * @return string
     */
    public function getPremiumStatusString(): string
    {
        return self::STATUSES_STR[$this->getPremiumStatus()];
    }

    /**
     * @param int $premiumStatus
     *
     * @return self
     */
    public function setPremiumStatus(int $premiumStatus): self
    {
        if (!in_array($premiumStatus, self::STATUSES)) {
            throw new InvalidArgumentException(
                "Invalid premium status ${premiumStatus}."
            );
        }
        $this->premiumStatus = $premiumStatus;

        return $this;
    }

    /**
     * @param string $status
     *
     * @return $this
     */
    public function setPremiumStatusString($status): self
    {
        $index = array_search($status, self::STATUSES_STR);
        $this->setPremiumStatus($index);

        return $this;
    }

    /**
     * @return Collection|Category[]
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    /**
     * @param Collection $categories
     *
     * @return self
     */
    public function setCategories(Collection $categories): self
    {
        $this->categories = $categories;

        return $this;
    }

    /**
     * @return Collection|Tag[]
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    /**
     * @param Collection $tags
     *
     * @return self
     */
    public function setTags(Collection $tags): self
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * @return int
     */
    public function getBumpPoints(): int
    {
        return $this->bumpPoints;
    }

    /**
     * @param int $bumpPoints
     *
     * @return self
     */
    public function setBumpPoints(int $bumpPoints): self
    {
        $this->bumpPoints = $bumpPoints;

        return $this;
    }

    /**
     * @param int $points
     *
     * @return self
     */
    public function incrementBumpPoints(int $points = 1) : self
    {
        return $this->setBumpPoints($this->getBumpPoints() + $points);
    }

    /**
     * @return int
     */
    public function getMembersOnline(): int
    {
        return $this->membersOnline;
    }

    /**
     * @param int $membersOnline
     *
     * @return self
     */
    public function setMembersOnline(int $membersOnline): self
    {
        $this->membersOnline = $membersOnline;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getInviteType(): ?string
    {
        return $this->inviteType;
    }

    /**
     * @param string $inviteType
     *
     * @return self
     */
    public function setInviteType(string $inviteType): self
    {
        if (!in_array($inviteType, self::INVITE_TYPES)) {
            throw new InvalidArgumentException(
                "Invalid invite type ${inviteType}."
            );
        }
        $this->inviteType = $inviteType;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getBotInviteChannelID(): ?int
    {
        return $this->botInviteChannelID;
    }

    /**
     * @param int $botInviteChannelID
     *
     * @return self
     */
    public function setBotInviteChannelID(int $botInviteChannelID): self
    {
        $this->botInviteChannelID = $botInviteChannelID;

        return $this;
    }

    /**
     * @return bool
     */
    public function isBotHumanCheck(): bool
    {
        return $this->botHumanCheck;
    }

    /**
     * @param bool $botHumanCheck
     *
     * @return self
     */
    public function setBotHumanCheck(bool $botHumanCheck): self
    {
        $this->botHumanCheck = $botHumanCheck;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getServerPassword(): ?string
    {
        return $this->serverPassword;
    }

    /**
     * @param string $serverPassword
     *
     * @return self
     */
    public function setServerPassword(string $serverPassword): self
    {
        $this->serverPassword = $serverPassword;

        return $this;
    }

    /**
     * @return bool
     */
    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    /**
     * @param bool $isPublic
     *
     * @return self
     */
    public function setIsPublic(bool $isPublic): self
    {
        $this->isPublic = $isPublic;

        return $this;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->isActive;
    }

    /**
     * @param bool $isActive
     *
     * @return self
     */
    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    /**
     * @param bool $isEnabled
     *
     * @return self
     */
    public function setIsEnabled(bool $isEnabled): self
    {
        $this->isEnabled = $isEnabled;

        return $this;
    }

    /**
     * @return Collection|ServerTeamMember[]
     */
    public function getTeamMembers(): Collection
    {
        return $this->teamMembers;
    }

    /**
     * @param Collection $teamMembers
     *
     * @return self
     */
    public function setTeamMembers(Collection $teamMembers): self
    {
        $this->teamMembers = $teamMembers;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getDateBumped(): ?DateTime
    {
        return $this->dateBumped;
    }

    /**
     * @param DateTime $dateBumped
     *
     * @return self
     */
    public function setDateBumped(DateTime $dateBumped): self
    {
        $this->dateBumped = $dateBumped;

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

    /**
     * @return DateTime
     */
    public function getDateUpdated(): DateTime
    {
        return $this->dateUpdated;
    }

    /**
     * @param DateTime $dateUpdated
     *
     * @return self
     */
    public function setDateUpdated(DateTime $dateUpdated): self
    {
        $this->dateUpdated = $dateUpdated;

        return $this;
    }

    /**
     * @return Category|null
     */
    public function getCategory1(): ?Category
    {
        $categories = $this->getCategories();
        if (isset($categories[0])) {
            return $categories[0];
        }

        return null;
    }

    /**
     * @param Category $category1
     *
     * @return self
     */
    public function setCategory1(Category $category1): self
    {
        $categories = $this->getCategories();
        $categories[0] = $category1;
        $this->setCategories($categories);

        return $this;
    }

    /**
     * @return Category|null
     */
    public function getCategory2(): ?Category
    {
        $categories = $this->getCategories();
        if (isset($categories[1])) {
            return $categories[1];
        }

        return null;
    }

    /**
     * @param Category $category2
     *
     * @return self
     */
    public function setCategory2(Category $category2): self
    {
        $categories = $this->getCategories();
        $categories[1] = $category2;
        $this->setCategories($categories);

        return $this;
    }

    /**
     * @return int
     */
    public function getNextBumpSeconds(): int
    {
        return $this->nextBumpSeconds;
    }

    /**
     * @param int $nextBumpSeconds
     *
     * @return self
     */
    public function setNextBumpSeconds(int $nextBumpSeconds): self
    {
        $this->nextBumpSeconds = $nextBumpSeconds;

        return $this;
    }

    /**
     * @return ServerEvent|null
     */
    public function getLastBumpEvent(): ?ServerEvent
    {
        return $this->lastBumpEvent;
    }

    /**
     * @param ServerEvent $lastBumpEvent
     *
     * @return self
     */
    public function setLastBumpEvent(?ServerEvent $lastBumpEvent): self
    {
        $this->lastBumpEvent = $lastBumpEvent;

        return $this;
    }

    /**
     * @return bool
     */
    public function isBumpReady(): bool
    {
        $dateBumped = $this->getDateBumped();
        if (!$dateBumped) {
            return true;
        }

        $window = $dateBumped->getTimestamp() + self::BUMP_PERIOD_SECONDS;
        $diff   = $window - time();

        return $diff <= 0;
    }

    /**
     * @return int
     */
    public function getPointsPerBump(): int
    {
        return self::POINTS_PER_BUMP[$this->getPremiumStatus()];
    }
}
