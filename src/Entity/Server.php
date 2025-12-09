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

/**
 * @ORM\Table(name="server", indexes={
 *     @ORM\Index(name="enabled_public_idx", columns={"is_enabled", "is_public"}),
 *     @ORM\Index(name="enabled_public_premium_idx", columns={"is_enabled", "is_public", "premium_status"}),
 *     @ORM\Index(name="enabled_public_bump_points_idx", columns={"is_enabled", "is_public", "premium_status", "bump_points", "date_bumped"}),
 *     @ORM\Index(name="enabled_public_members_online_idx", columns={"is_enabled", "is_public", "members_online", "bump_points", "date_bumped"})
 * })
 * @ORM\Entity(repositoryClass="App\Repository\ServerRepository")
 */
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

    /**
     * @ORM\Id
     * @ORM\Column(type="bigint", options={"unsigned"=true})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var int
     * @ORM\Column(type="bigint", options={"unsigned"=true})
     */
    protected $discordID;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="User", inversedBy="servers")
     * @ORM\JoinColumn(name="user_id", onDelete="CASCADE", referencedColumnName="id")
     */
    protected $user;

    /**
     * @var string
     * @ORM\Column(type="string", length=100, unique=true)
     */
    protected $slug;

    /**
     * @var string
     * @ORM\Column(type="string", length=100)
     */
    protected $name;

    /**
     * @var string
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    protected $iconHash;

    /**
     * @var string
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    protected $bannerHash;

    /**
     * @var Media
     * @ORM\OneToOne(targetEntity="Media", cascade={"persist"})
     */
    protected $iconMedia;

    /**
     * @var Media
     * @ORM\OneToOne(targetEntity="Media", cascade={"persist"})
     */
    protected $bannerMedia;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $vanityURL;

    /**
     * @var string
     * @ORM\Column(type="string", length=160, nullable=true)
     */
    protected $summary;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    protected $description;

    /**
     * @var int
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    protected $bumpPoints = 0;

    /**
     * @var int
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    protected $membersOnline = 0;

    /**
     * @var int
     * @ORM\Column(type="smallint")
     */
    protected $premiumStatus;

    /**
     * @var Collection
     * @ORM\ManyToMany(targetEntity="Category", cascade={"persist"})
     * @ORM\JoinTable(
     *     name="server_categories",
     *     joinColumns={@ORM\JoinColumn(name="server_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="category_id", referencedColumnName="id")}
     * )
     */
    protected $categories;

    /**
     * @var Collection
     * @ORM\ManyToMany(targetEntity="Tag", cascade={"persist"})
     * @ORM\JoinTable(
     *     name="server_tags",
     *     joinColumns={@ORM\JoinColumn(name="server_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="tag_id", referencedColumnName="id")}
     * )
     */
    protected $tags;

    /**
     * @var Collection
     * @ORM\OneToMany(targetEntity="ServerTeamMember", mappedBy="server")
     */
    protected $teamMembers;

    /**
     * @var string
     * @ORM\Column(type="string", length=20)
     */
    protected $inviteType;

    /**
     * @var int
     * @ORM\Column(type="bigint", options={"unsigned"=true}, nullable=true)
     */
    protected $botInviteChannelID;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $botHumanCheck = false;

    /**
     * @var string
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    protected $serverPassword;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $isPublic = true;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $isActive = true;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $isEnabled = true;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $dateBumped;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime")
     */
    protected $dateCreated;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime")
     * @Gedmo\Timestampable(on="update")
     */
    protected $dateUpdated;

    /**
     * @var int
     */
    protected $nextBumpSeconds = 0;

    /**
     * @var ServerEvent
     */
    protected $lastBumpEvent;

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
     * @return Server
     */
    public function setDiscordID($discordID): Server
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
     * @return Server
     */
    public function setUser(User $user): Server
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
     * @return Server
     */
    public function setSlug(string $slug): Server
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
     * @return Server
     */
    public function setName(string $name): Server
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
     * @return Server
     */
    public function setIconHash(string $iconHash): Server
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
     * @return Server
     */
    public function setBannerHash(string $bannerHash): Server
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
     * @return Server
     */
    public function setIconMedia(Media $iconMedia): Server
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
     * @return Server
     */
    public function setBannerMedia(Media $bannerMedia): Server
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
     * @return Server
     */
    public function setVanityURL(string $vanityURL): Server
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
     * @return Server
     */
    public function setSummary(string $summary): Server
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
     * @return Server
     */
    public function setDescription(string $description): Server
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
     * @return Server
     */
    public function setPremiumStatus(int $premiumStatus): Server
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
    public function setPremiumStatusString($status): Server
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
     * @return Server
     */
    public function setCategories(Collection $categories): Server
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
     * @return Server
     */
    public function setTags(Collection $tags): Server
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
     * @return Server
     */
    public function setBumpPoints(int $bumpPoints): Server
    {
        $this->bumpPoints = $bumpPoints;

        return $this;
    }

    /**
     * @param int $points
     *
     * @return Server
     */
    public function incrementBumpPoints(int $points = 1) : Server
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
     * @return Server
     */
    public function setMembersOnline(int $membersOnline): Server
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
     * @return Server
     */
    public function setInviteType(string $inviteType): Server
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
     * @return Server
     */
    public function setBotInviteChannelID(int $botInviteChannelID): Server
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
     * @return Server
     */
    public function setBotHumanCheck(bool $botHumanCheck): Server
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
     * @return Server
     */
    public function setServerPassword(string $serverPassword): Server
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
     * @return Server
     */
    public function setIsPublic(bool $isPublic): Server
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
     * @return Server
     */
    public function setIsActive(bool $isActive): Server
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
     * @return Server
     */
    public function setIsEnabled(bool $isEnabled): Server
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
     * @return Server
     */
    public function setTeamMembers(Collection $teamMembers): Server
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
     * @return Server
     */
    public function setDateBumped(DateTime $dateBumped): Server
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
     * @return Server
     */
    public function setDateCreated(DateTime $dateCreated): Server
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
     * @return Server
     */
    public function setDateUpdated(DateTime $dateUpdated): Server
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
     * @return Server
     */
    public function setCategory1(Category $category1): Server
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
     * @return Server
     */
    public function setCategory2(Category $category2): Server
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
     * @return Server
     */
    public function setNextBumpSeconds(int $nextBumpSeconds): Server
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
     * @return Server
     */
    public function setLastBumpEvent(?ServerEvent $lastBumpEvent): Server
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
