<?php
namespace App\Entity;

use App\Admin\LoggableEntityInterface;
use Doctrine\ORM\Mapping as ORM;
use DateTime;
use Gedmo\Mapping\Annotation as Gedmo;
use Exception;

/**
 * @ORM\Table(name="category")
 * @ORM\Entity(repositoryClass="App\Repository\CategoryRepository")
 */
class Category implements LoggableEntityInterface
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", options={"unsigned"=true})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=100)
     */
    protected $name;

    /**
     * @var string
     * @ORM\Column(type="string", length=100)
     */
    protected $slug;

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
    public function __toString()
    {
        return $this->getName() ?? '';
    }

    /**
     * @return string
     */
    public function getLoggableMessage()
    {
        return sprintf('category #%d "%s"', $this->getId(), $this->getName());
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
     * @return Category
     */
    public function setName(string $name): Category
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getSlug(): ?string
    {
        return $this->slug;
    }

    /**
     * @param string $slug
     *
     * @return Category
     */
    public function setSlug(string $slug): Category
    {
        $this->slug = $slug;

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
     * @return Category
     */
    public function setDateCreated(DateTime $dateCreated): Category
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
     * @return Category
     */
    public function setDateUpdated(DateTime $dateUpdated): Category
    {
        $this->dateUpdated = $dateUpdated;

        return $this;
    }
}
