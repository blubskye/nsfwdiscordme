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

/**
 * @ORM\Table(name="user")
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 */
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

    /**
     * @ORM\Id
     * @ORM\Column(type="integer", options={"unsigned"=true})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var array
     * @ORM\Column(type="array")
     */
    protected $roles;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $isEnabled;

    /**
     * @var AccessToken
     * @ORM\OneToOne(targetEntity="AccessToken", mappedBy="user", cascade={"persist"})
     */
    protected $discordAccessToken;

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
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $discordEmail;

    /**
     * @var string
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    protected $discordAvatar;

    /**
     * @var string
     * @ORM\Column(type="string", length=4, nullable=true)
     */
    protected $discordDiscriminator;

    /**
     * @var Collection
     * @ORM\OneToMany(targetEntity="Server", mappedBy="user")
     */
    protected $servers;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $googleAuthenticatorSecret;

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
     * @var DateTime
     * @ORM\Column(type="datetime")
     */
    protected $dateLastLogin;

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
        return $this->getUsername();
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
     * @return User
     */
    public function setIsEnabled(bool $isEnabled): User
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
     * @return User
     */
    public function setDiscordAccessToken(AccessToken $discordAccessToken): User
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
     * @return User
     */
    public function setDiscordID(int $discordID): User
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
     * @return User
     */
    public function setDiscordUsername(string $discordUsername): User
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
     * @return User
     */
    public function setDiscordEmail(string $discordEmail): User
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
     * @return User
     */
    public function setDiscordAvatar(string $discordAvatar): User
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
     * @return User
     */
    public function setDiscordDiscriminator(string $discordDiscriminator): User
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
     * @return User
     */
    public function setServers(Collection $servers): User
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
     * @return User
     */
    public function setDateCreated(DateTime $dateCreated): User
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
     * @return User
     */
    public function setDateUpdated(DateTime $dateUpdated): User
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
     * @return User
     */
    public function setDateLastLogin(DateTime $dateLastLogin): User
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
     * @return User
     */
    public function setRoles(array $roles): User
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
     * @return User
     */
    public function addRole($role): User
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
     * @return User
     */
    public function removeRole($role): User
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
        return $this->getUsername();
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
     * @return User
     */
    public function setGoogleAuthenticatorSecret(?string $googleAuthenticatorSecret): User
    {
        $this->googleAuthenticatorSecret = $googleAuthenticatorSecret;

        return $this;
    }
}
