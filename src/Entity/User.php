<?php
namespace App\Entity;

use App\Admin\LoggableEntityInterface;
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
    const ROLE_USER        = 'ROLE_USER';
    const ROLE_ADMIN       = 'ROLE_ADMIN';
    const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    const ROLES = [
        self::ROLE_USER,
        self::ROLE_ADMIN,
        self::ROLE_SUPER_ADMIN
    ];

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

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->dateCreated   = new DateTime();
        $this->dateUpdated   = new DateTime();
        $this->dateLastLogin = new DateTime();
        $this->servers       = new ArrayCollection();
        $this->roles         = [self::ROLE_USER];
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getUserIdentifier();
    }

    /**
     * @return string
     */
    public function getLoggableMessage()
    {
        return sprintf(
            'user #%d "%s#%s"',
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
     * @return AccessToken
     */
    public function getDiscordAccessToken(): ?AccessToken
    {
        return $this->discordAccessToken;
    }

    /**
     * @param AccessToken $discordAccessToken
     *
     * @return self
     */
    public function setDiscordAccessToken(AccessToken $discordAccessToken): self
    {
        $this->discordAccessToken = $discordAccessToken;

        return $this;
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
    public function getDiscordEmail(): ?string
    {
        return $this->discordEmail;
    }

    /**
     * @param string $discordEmail
     *
     * @return self
     */
    public function setDiscordEmail(string $discordEmail): self
    {
        $this->discordEmail = $discordEmail;

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
     * @return string
     */
    public function getDiscordAvatarURL(): string
    {
        return sprintf('https://cdn.discordapp.com/avatars/%s/%s.png', $this->getDiscordID(), $this->getDiscordAvatar());
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
     * @return Collection|Server[]
     */
    public function getServers(): Collection
    {
        return $this->servers;
    }

    /**
     * @param Collection $servers
     *
     * @return self
     */
    public function setServers(Collection $servers): self
    {
        $this->servers = $servers;

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
     * @return DateTime
     */
    public function getDateLastLogin(): DateTime
    {
        return $this->dateLastLogin;
    }

    /**
     * @param DateTime $dateLastLogin
     *
     * @return self
     */
    public function setDateLastLogin(DateTime $dateLastLogin): self
    {
        $this->dateLastLogin = $dateLastLogin;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @param string $role
     *
     * @return bool
     */
    public function hasRole($role): bool
    {
        return in_array(strtoupper($role), $this->getRoles(), true);
    }

    /**
     * @param array $roles
     *
     * @return self
     */
    public function setRoles(array $roles): self
    {
        $this->roles = [];
        foreach ($roles as $role) {
            $this->addRole($role);
        }

        return $this;
    }

    /**
     * @param string $role
     *
     * @return self
     */
    public function addRole($role): self
    {
        $role = strtoupper($role);
        if (!in_array($role, self::ROLES)) {
            throw new InvalidArgumentException(
                "Invalid role ${role}."
            );
        }
        if ($role !== self::ROLE_SUPER_ADMIN && !in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }

        return $this;
    }

    /**
     * @param string $role
     *
     * @return self
     */
    public function removeRole($role): self
    {
        $role = strtoupper($role);
        if (!in_array($role, self::ROLES)) {
            throw new InvalidArgumentException(
                "Invalid role ${role}."
            );
        }
        $index = array_search($role, $this->roles);
        if ($index !== false) {
            unset($this->roles[$index]);
        }

        if (empty($this->roles)) {
            $this->roles = [self::ROLE_USER];
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return '';
    }

    /**
     * @return string|null
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->getDiscordUsername() ?? '';
    }

    /**
     * @return string
     */
    public function getUserIdentifier(): string
    {
        return $this->getDiscordUsername() ?? '';
    }

    /**
     * {@inheritDoc}
     */
    public function eraseCredentials()
    {
        // Nothing here
    }

    /**
     * Return true if the user should do two-factor authentication.
     *
     * @return bool
     */
    public function isGoogleAuthenticatorEnabled(): bool
    {
        return $this->googleAuthenticatorSecret ? true : false;
    }

    /**
     * Return the user name.
     *
     * @return string
     */
    public function getGoogleAuthenticatorUsername(): string
    {
        return $this->getUserIdentifier();
    }

    /**
     * Return the Google Authenticator secret
     * When an empty string is returned, the Google authentication is disabled.
     *
     * @return string
     */
    public function getGoogleAuthenticatorSecret(): ?string
    {
        return $this->googleAuthenticatorSecret;
    }

    /**
     * @param string|null $googleAuthenticatorSecret
     *
     * @return self
     */
    public function setGoogleAuthenticatorSecret(?string $googleAuthenticatorSecret): self
    {
        $this->googleAuthenticatorSecret = $googleAuthenticatorSecret;

        return $this;
    }
}
