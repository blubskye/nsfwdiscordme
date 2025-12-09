<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTime;
use Exception;

/**
 * @ORM\Table(name="access_token")
 * @ORM\Entity(repositoryClass="App\Repository\AccessTokenRepository")
 */
class AccessToken
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(type="bigint", options={"unsigned"=true})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var User
     * @ORM\OneToOne(targetEntity="User", inversedBy="discordAccessToken")
     * @ORM\JoinColumn(name="user_id", onDelete="CASCADE", referencedColumnName="id")
     */
    protected $user;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    protected $token;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    protected $refreshToken;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    protected $scope;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    protected $type;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime")
     */
    protected $dateExpires;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime")
     */
    protected $dateCreated;

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
     * @return AccessToken
     */
    public function setUser(User $user): AccessToken
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
     * @return AccessToken
     */
    public function setToken(string $token): AccessToken
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
     * @return AccessToken
     */
    public function setRefreshToken(string $refreshToken): AccessToken
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
     * @return AccessToken
     */
    public function setScope(string $scope): AccessToken
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
     * @return AccessToken
     */
    public function setType(string $type): AccessToken
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
     * @return AccessToken
     */
    public function setDateExpires(DateTime $dateExpires): AccessToken
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
     * @return AccessToken
     */
    public function setDateCreated(DateTime $dateCreated): AccessToken
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }
}
