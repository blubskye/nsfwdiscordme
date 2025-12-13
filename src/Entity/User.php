<?php
namespace App\Entity;

use App\Admin\LoggableEntityInterface;
use App\Enum\UserRole;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use InvalidArgumentException;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Table(name: 'user')]
#[ORM\Index(name: 'discord_id_idx', columns: ['discord_id'])]
#[ORM\Index(name: 'discord_email_idx', columns: ['discord_email'])]
#[ORM\Index(name: 'discord_username_discriminator_idx', columns: ['discord_username', 'discord_discriminator'])]
#[ORM\Entity(repositoryClass: 'App\Repository\UserRepository')]
class User implements UserInterface, LoggableEntityInterface
{
    // Keep constants for backward compatibility
    public const ROLE_USER = 'ROLE_USER';
    public const ROLE_ADMIN = 'ROLE_ADMIN';
    public const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(type: 'array')]
    protected array $roles = [];

    #[ORM\Column(type: 'boolean')]
    protected bool $isEnabled;

    #[ORM\OneToOne(targetEntity: AccessToken::class, mappedBy: 'user', cascade: ['persist'])]
    protected ?AccessToken $discordAccessToken = null;

    #[ORM\Column(type: 'bigint', options: ['unsigned' => true], nullable: true)]
    protected ?int $discordID = null;

    #[ORM\Column(type: 'string', length: 32, nullable: true)]
    protected ?string $discordUsername = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    protected ?string $discordEmail = null;

    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    protected ?string $discordAvatar = null;

    #[ORM\Column(type: 'string', length: 4, nullable: true)]
    protected ?string $discordDiscriminator = null;

    #[ORM\OneToMany(targetEntity: Server::class, mappedBy: 'user')]
    protected Collection $servers;

    #[ORM\Column(type: 'string', nullable: true)]
    protected ?string $googleAuthenticatorSecret = null;

    #[ORM\Column(type: 'datetime')]
    protected DateTime $dateCreated;

    #[ORM\Column(type: 'datetime')]
    #[Gedmo\Timestampable(on: 'update')]
    protected DateTime $dateUpdated;

    #[ORM\Column(type: 'datetime')]
    protected DateTime $dateLastLogin;

    public function __construct()
    {
        $this->dateCreated = new DateTime();
        $this->dateUpdated = new DateTime();
        $this->dateLastLogin = new DateTime();
        $this->servers = new ArrayCollection();
        $this->roles = [UserRole::USER->value];
    }

    public function __toString(): string
    {
        return $this->getUserIdentifier();
    }

    public function getLoggableMessage(): string
    {
        return sprintf(
            'user #%d "%s#%s"',
            $this->getId(),
            $this->getDiscordUsername(),
            $this->getDiscordDiscriminator()
        );
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDiscordAccessToken(): ?AccessToken
    {
        return $this->discordAccessToken;
    }

    public function setDiscordAccessToken(AccessToken $discordAccessToken): self
    {
        $this->discordAccessToken = $discordAccessToken;

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

    public function getDiscordEmail(): ?string
    {
        return $this->discordEmail;
    }

    public function setDiscordEmail(string $discordEmail): self
    {
        $this->discordEmail = $discordEmail;

        return $this;
    }

    public function getDiscordAvatar(): ?string
    {
        return $this->discordAvatar;
    }

    public function getDiscordAvatarURL(): string
    {
        return sprintf('https://cdn.discordapp.com/avatars/%s/%s.png', $this->getDiscordID(), $this->getDiscordAvatar());
    }

    public function setDiscordAvatar(string $discordAvatar): self
    {
        $this->discordAvatar = $discordAvatar;

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

    public function getServers(): Collection
    {
        return $this->servers;
    }

    public function setServers(Collection $servers): self
    {
        $this->servers = $servers;

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

    public function getDateLastLogin(): DateTime
    {
        return $this->dateLastLogin;
    }

    public function setDateLastLogin(DateTime $dateLastLogin): self
    {
        $this->dateLastLogin = $dateLastLogin;

        return $this;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function hasRole(string|UserRole $role): bool
    {
        $roleValue = $role instanceof UserRole ? $role->value : strtoupper($role);
        return in_array($roleValue, $this->getRoles(), true);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = [];
        foreach ($roles as $role) {
            $this->addRole($role);
        }

        return $this;
    }

    public function addRole(string|UserRole $role): self
    {
        $roleValue = $role instanceof UserRole ? $role->value : strtoupper($role);

        if (!array_any(UserRole::values(), fn($r) => $r === $roleValue)) {
            throw new InvalidArgumentException("Invalid role {$roleValue}.");
        }

        if ($roleValue !== UserRole::SUPER_ADMIN->value && !in_array($roleValue, $this->roles, true)) {
            $this->roles[] = $roleValue;
        }

        return $this;
    }

    public function removeRole(string|UserRole $role): self
    {
        $roleValue = $role instanceof UserRole ? $role->value : strtoupper($role);

        if (!array_any(UserRole::values(), fn($r) => $r === $roleValue)) {
            throw new InvalidArgumentException("Invalid role {$roleValue}.");
        }

        $index = array_search($roleValue, $this->roles);
        if ($index !== false) {
            unset($this->roles[$index]);
        }

        if (empty($this->roles)) {
            $this->roles = [UserRole::USER->value];
        }

        return $this;
    }

    public function getPassword(): string
    {
        return '';
    }

    public function getSalt(): ?string
    {
        return null;
    }

    public function getUsername(): string
    {
        return $this->getDiscordUsername() ?? '';
    }

    public function getUserIdentifier(): string
    {
        return $this->getDiscordUsername() ?? '';
    }

    public function eraseCredentials(): void
    {
        // Nothing here
    }

    public function isGoogleAuthenticatorEnabled(): bool
    {
        return (bool) $this->googleAuthenticatorSecret;
    }

    public function getGoogleAuthenticatorUsername(): string
    {
        return $this->getUserIdentifier();
    }

    public function getGoogleAuthenticatorSecret(): ?string
    {
        return $this->googleAuthenticatorSecret;
    }

    public function setGoogleAuthenticatorSecret(?string $googleAuthenticatorSecret): self
    {
        $this->googleAuthenticatorSecret = $googleAuthenticatorSecret;

        return $this;
    }
}
