<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTime;
use Exception;

/**
 * @ORM\Table(name="server_action")
 * @ORM\Entity(repositoryClass="App\Repository\ServerActionRepository")
 */
class ServerAction
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
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="user_id", onDelete="CASCADE", referencedColumnName="id", nullable=false)
     */
    protected $user;

    /**
     * @var Server
     * @ORM\ManyToOne(targetEntity="Server")
     * @ORM\JoinColumn(name="server_id", onDelete="CASCADE", referencedColumnName="id", nullable=false)
     */
    protected $server;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    protected $action;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime")
     */
    protected $dateCreated;

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
     * @return ServerAction
     */
    public function setUser(User $user): ServerAction
    {
        $this->user = $user;

        return $this;
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
     * @return ServerAction
     */
    public function setServer(Server $server): ServerAction
    {
        $this->server = $server;

        return $this;
    }

    /**
     * @return string
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * @param string $action
     *
     * @return ServerAction
     */
    public function setAction(string $action): ServerAction
    {
        $this->action = $action;

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
     * @return ServerAction
     */
    public function setDateCreated(DateTime $dateCreated): ServerAction
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }
}
