<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTime;
use Exception;

#[ORM\Table(name: 'access_token')]
#[ORM\Entity(repositoryClass: 'App\Repository\AccessTokenRepository')]
class AccessToken
{
    #[ORM\Id]
    #[ORM\Column(type: 'bigint', options: ['unsigned' => true])]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'discordAccessToken')]
    #[ORM\JoinColumn(name: 'user_id', onDelete: 'CASCADE', referencedColumnName: 'id')]
    protected User $user;

    #[ORM\Column(type: 'string', length: 255)]
    protected string $token;

    #[ORM\Column(type: 'string', length: 255)]
    protected string $refreshToken;

    #[ORM\Column(type: 'string', length: 255)]
    protected string $scope;

    #[ORM\Column(type: 'string', length: 255)]
    protected string $type;

    #[ORM\Column(type: 'datetime')]
    protected DateTime $dateExpires;

    #[ORM\Column(type: 'datetime')]
    protected DateTime $dateCreated;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->dateCreated = new DateTime();
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
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
     * @return string
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * @param string $token
     *
     * @return self
     */
    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @return string
     */
    public function getRefreshToken(): string
    {
        return $this->refreshToken;
    }

    /**
     * @param string $refreshToken
     *
     * @return self
     */
    public function setRefreshToken(string $refreshToken): self
    {
        $this->refreshToken = $refreshToken;

        return $this;
    }

    /**
     * @return string
     */
    public function getScope(): string
    {
        return $this->scope;
    }

    /**
     * @param string $scope
     *
     * @return self
     */
    public function setScope(string $scope): self
    {
        $this->scope = $scope;

        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return self
     */
    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return DateTime
     */
    public function getDateExpires(): DateTime
    {
        return $this->dateExpires;
    }

    /**
     * @param DateTime $dateExpires
     *
     * @return self
     */
    public function setDateExpires(DateTime $dateExpires): self
    {
        $this->dateExpires = $dateExpires;

        return $this;
    }

    /**
     * @return bool
     */
    public function isExpired(): bool
    {
        try {
            return $this->dateExpires < new DateTime();
        } catch(Exception $e) {
            return true;
        }
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
}
