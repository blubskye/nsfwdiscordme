<?php
namespace App\Entity;

use App\Admin\LoggableEntityInterface;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use DateTime;
use Exception;

#[ORM\Table(name: 'tag')]
#[ORM\Entity(repositoryClass: 'App\Repository\TagRepository')]
class Tag implements LoggableEntityInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(type: 'string', length: 100, unique: true)]
    protected string $name;

    #[ORM\Column(type: 'datetime')]
    protected DateTime $dateCreated;

    #[ORM\Column(type: 'datetime')]
    #[Gedmo\Timestampable(on: 'update')]
    protected DateTime $dateUpdated;

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
    public function __toString(): string
    {
        return $this->getName() ?? '';
    }

    /**
     * @return string
     */
    public function getLoggableMessage()
    {
        return sprintf('tag #%d "%s"', $this->getId(), $this->getName());
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return self
     */
    public function setName(string $name): self
    {
        $this->name = $name;

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
}
