<?php
namespace App\Entity;

use App\Admin\LoggableEntityInterface;
use App\Enum\ServerTeamRole;
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
    // Keep constants for backward compatibility
    public const ROLE_OWNER = 'owner';
    public const ROLE_MANAGER = 'manager';
    public const ROLE_EDITOR = 'editor';
    public const ROLES = [
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
     * @throws Exception
     */
    public function __construct()
    {
        $this->dateCreated = new DateTime();
    }

    public function __toString(): string
    {
        return (string) ($this->getDiscordID() ?? '');
    }

    public function getLoggableMessage(): string
    {
        return sprintf(
            'team member #%d "%s#%s"',
            $this->getId(),
            $this->getDiscordUsername(),
            $this->getDiscordDiscriminator()
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
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

    public function getDiscordUsername(): ?string
    {
        return $this->discordUsername;
    }

    public function setDiscordUsername(string $discordUsername): self
    {
        $this->discordUsername = $discordUsername;

        return $this;
    }

    public function getDiscordDiscriminator(): ?string
    {
        return $this->discordDiscriminator;
    }

    public function setDiscordDiscriminator(string $discordDiscriminator): self
    {
        $this->discordDiscriminator = $discordDiscriminator;

        return $this;
    }

    public function getDiscordAvatar(): ?string
    {
        return $this->discordAvatar;
    }

    public function setDiscordAvatar(string $discordAvatar): self
    {
        $this->discordAvatar = $discordAvatar;

        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function getRoleEnum(): ServerTeamRole
    {
        return ServerTeamRole::from($this->role);
    }

    public function setRole(string|ServerTeamRole $role): self
    {
        $value = $role instanceof ServerTeamRole ? $role->value : $role;

        if (!array_any(ServerTeamRole::values(), fn($r) => $r === $value)) {
            throw new InvalidArgumentException("Invalid server role {$value}.");
        }
        $this->role = $value;

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

    public function getDateLastAction(): ?DateTime
    {
        return $this->dateLastAction;
    }

    public function setDateLastAction(DateTime $dateLastAction): self
    {
        $this->dateLastAction = $dateLastAction;

        return $this;
    }
}
