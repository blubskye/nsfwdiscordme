<?php
namespace App\Entity;

use App\Admin\LoggableEntityInterface;
use Doctrine\ORM\Mapping as ORM;
use DateTime;
use Exception;
use InvalidArgumentException;

#[ORM\Table(name: 'server_team_member')]
#[ORM\UniqueConstraint(columns: ['server_id', 'discord_username', 'discord_discriminator'])]
#[ORM\Index(name: 'server_id_idx', columns: ['server_id'])]
#[ORM\Index(name: 'user_id_idx', columns: ['user_id'])]
#[ORM\Entity(repositoryClass: 'App\Repository\ServerTeamMemberRepository')]
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

    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Server::class, inversedBy: 'teamMembers')]
    #[ORM\JoinColumn(name: 'server_id', onDelete: 'CASCADE', referencedColumnName: 'id')]
    protected Server $server;

    #[ORM\ManyToOne(targetEntity: User::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'user_id', onDelete: 'CASCADE', referencedColumnName: 'id', nullable: true)]
    protected ?User $user = null;

    #[ORM\Column(type: 'bigint', options: ['unsigned' => true], nullable: true)]
    protected ?int $discordID = null;

    #[ORM\Column(type: 'string', length: 32, nullable: true)]
    protected ?string $discordUsername = null;

    #[ORM\Column(type: 'string', length: 4, nullable: true)]
    protected ?string $discordDiscriminator = null;

    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    protected ?string $discordAvatar = null;

    #[ORM\Column(type: 'string', length: 20)]
    protected string $role;

    #[ORM\Column(type: 'datetime')]
    protected DateTime $dateCreated;

    #[ORM\Column(type: 'datetime', nullable: true)]
    protected ?DateTime $dateLastAction = null;

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
    public function getDiscordID(): ?int
    {
        return $this->discordID;
    }

    /**
     * @param int $discordID
     *
     * @return self
     */
    public function setDiscordID(int $discordID): self
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
     * @return self
     */
    public function setDiscordUsername(string $discordUsername): self
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
     * @return self
     */
    public function setDiscordDiscriminator(string $discordDiscriminator): self
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
     * @return self
     */
    public function setDiscordAvatar(string $discordAvatar): self
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
     * @return self
     */
    public function setRole(string $role): self
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
    public function getDateLastAction(): ?DateTime
    {
        return $this->dateLastAction;
    }

    /**
     * @param DateTime $dateLastAction
     *
     * @return self
     */
    public function setDateLastAction(DateTime $dateLastAction): self
    {
        $this->dateLastAction = $dateLastAction;

        return $this;
    }
}
