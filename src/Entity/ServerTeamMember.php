<?php
namespace App\Entity;

use App\Admin\LoggableEntityInterface;
use Doctrine\ORM\Mapping as ORM;
use DateTime;
use Exception;
use InvalidArgumentException;

/**
 * @ORM\Table(name="server_team_member",
 *     uniqueConstraints={
 *          @ORM\UniqueConstraint(columns={"server_id", "discord_username", "discord_discriminator"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="App\Repository\ServerTeamMemberRepository")
 */
class ServerTeamMember implements LoggableEntityInterface
{
    const ROLE_OWNER   = 'owner';
    const ROLE_MANAGER = 'manager';
    const ROLE_EDITOR  = 'editor';

    const ROLES = [
        self::ROLE_OWNER,
        self::ROLE_MANAGER,
        self::ROLE_EDITOR
    ];

    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(type="integer", options={"unsigned"=true})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Server
     * @ORM\ManyToOne(targetEntity="Server", inversedBy="teamMembers")
     * @ORM\JoinColumn(name="server_id", onDelete="CASCADE", referencedColumnName="id")
     */
    protected $server;

    /**
     * @var User
     * @ORM\ManyToOne(targetEntity="User", cascade={"persist"})
     * @ORM\JoinColumn(name="user_id", onDelete="CASCADE", referencedColumnName="id", nullable=true)
     */
    protected $user;

    /**
     * @var int
     * @ORM\Column(type="bigint", options={"unsigned"=true}, nullable=true)
     */
    protected $discordID;

    /**
     * @var string
     * @ORM\Column(type="string", length=32, nullable=true)
     */
    protected $discordUsername;

    /**
     * @var string
     * @ORM\Column(type="string", length=4, nullable=true)
     */
    protected $discordDiscriminator;

    /**
     * @var string
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    protected $discordAvatar;

    /**
     * @var string
     * @ORM\Column(type="string", length=20)
     */
    protected $role;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime")
     */
    protected $dateCreated;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $dateLastAction;

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
     * @return string
     */
    public function __toString(): string
    {
        return (string)($this->getDiscordID() ?? '');
    }

    /**
     * @return string
     */
    public function getLoggableMessage()
    {
        return sprintf(
            'team member #%d "%s#%s"',
            $this->getId(),
            $this->getDiscordUsername(),
            $this->getDiscordDiscriminator()
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
     * @return ServerTeamMember
     */
    public function setServer(Server $server): ServerTeamMember
    {
        $this->server = $server;

        return $this;
    }

    /**
     * @return User
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @param User $user
     *
     * @return ServerTeamMember
     */
    public function setUser(User $user): ServerTeamMember
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return int
     */
    public function getDiscordID(): ?int
    {
        return $this->discordID;
    }

    /**
     * @param int $discordID
     *
     * @return ServerTeamMember
     */
    public function setDiscordID(int $discordID): ServerTeamMember
    {
        $this->discordID = $discordID;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDiscordUsername(): ?string
    {
        return $this->discordUsername;
    }

    /**
     * @param string $discordUsername
     *
     * @return ServerTeamMember
     */
    public function setDiscordUsername(string $discordUsername): ServerTeamMember
    {
        $this->discordUsername = $discordUsername;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDiscordDiscriminator(): ?string
    {
        return $this->discordDiscriminator;
    }

    /**
     * @param string $discordDiscriminator
     *
     * @return ServerTeamMember
     */
    public function setDiscordDiscriminator(string $discordDiscriminator): ServerTeamMember
    {
        $this->discordDiscriminator = $discordDiscriminator;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDiscordAvatar(): ?string
    {
        return $this->discordAvatar;
    }

    /**
     * @param string $discordAvatar
     *
     * @return ServerTeamMember
     */
    public function setDiscordAvatar(string $discordAvatar): ServerTeamMember
    {
        $this->discordAvatar = $discordAvatar;

        return $this;
    }

    /**
     * @return string
     */
    public function getRole(): string
    {
        return $this->role;
    }

    /**
     * @param string $role
     *
     * @return ServerTeamMember
     */
    public function setRole(string $role): ServerTeamMember
    {
        if (!in_array($role, self::ROLES)) {
            throw new InvalidArgumentException(
                "Invalid server role ${role}."
            );
        }
        $this->role = $role;

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
     * @return ServerTeamMember
     */
    public function setDateCreated(DateTime $dateCreated): ServerTeamMember
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getDateLastAction(): ?DateTime
    {
        return $this->dateLastAction;
    }

    /**
     * @param DateTime $dateLastAction
     *
     * @return ServerTeamMember
     */
    public function setDateLastAction(DateTime $dateLastAction): ServerTeamMember
    {
        $this->dateLastAction = $dateLastAction;

        return $this;
    }
}
