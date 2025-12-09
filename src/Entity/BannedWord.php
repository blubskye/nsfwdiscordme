<?php
namespace App\Entity;

use App\Admin\LoggableEntityInterface;
use Doctrine\ORM\Mapping as ORM;
use DateTime;
use Exception;

#[ORM\Table(name: 'banned_word')]
#[ORM\UniqueConstraint(columns: ['word'])]
#[ORM\Entity(repositoryClass: 'App\Repository\BannedWordRepository')]
class BannedWord implements LoggableEntityInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer', options: ['unsigned' => true])]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(type: 'string', length: 60, nullable: true)]
    protected ?string $word = null;

    #[ORM\Column(type: 'datetime')]
    protected DateTime $dateCreated;

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
     * @return string
     */
    public function getLoggableMessage()
    {
        return sprintf('banned word #%d "%s"', $this->getId(), $this->getWord());
    }

    /**
     * @return int
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getWord(): ?string
    {
        return $this->word;
    }

    /**
     * @param string $word
     *
     * @return self
     */
    public function setWord(string $word): self
    {
        $this->word = $word;

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
}
