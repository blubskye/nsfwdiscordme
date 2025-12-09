<?php
namespace App\Entity;

use App\Admin\LoggableEntityInterface;
use Doctrine\ORM\Mapping as ORM;
use DateTime;
use Exception;
use Gedmo\Mapping\Annotation as Gedmo;
use InvalidArgumentException;

/**
 * @ORM\Table(name="media")
 * @ORM\Entity(repositoryClass="App\Repository\MediaRepository")
 */
class Media implements LoggableEntityInterface
{
    const ADAPTERS = [
        'aws',
        'local'
    ];

    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(type="bigint", options={"unsigned"=true})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=20)
     */
    protected $name;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    protected $path;

    /**
     * @var string
     * @ORM\Column(type="string", length=20)
     */
    protected $adapter;

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
     * Constructor
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->dateCreated = new DateTime();
        $this->dateUpdated = new DateTime();
    }

    /**
     * @return string
     */
    public function getLoggableMessage()
    {
        return sprintf('media #%d "%s"', $this->getId(), $this->getPath());
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Media
     */
    public function setName(string $name): Media
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param string $path
     *
     * @return Media
     */
    public function setPath(string $path): Media
    {
        $this->path = $path;

        return $this;
    }

    /**
     * @return string
     */
    public function getAdapter(): string
    {
        return $this->adapter;
    }

    /**
     * @param string $adapter
     *
     * @return Media
     */
    public function setAdapter(string $adapter): Media
    {
        if (!in_array($adapter, self::ADAPTERS)) {
            throw new InvalidArgumentException("Invalid adapter.");
        }
        $this->adapter = $adapter;

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
     * @return Media
     */
    public function setDateCreated(DateTime $dateCreated): Media
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
     * @return Media
     */
    public function setDateUpdated(DateTime $dateUpdated): Media
    {
        $this->dateUpdated = $dateUpdated;

        return $this;
    }
}
