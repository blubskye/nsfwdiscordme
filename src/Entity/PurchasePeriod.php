<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTime;
use Exception;

#[ORM\Table(name: 'purchase_period')]
#[ORM\Index(columns: ['is_complete', 'date_expires'])]
#[ORM\Entity(repositoryClass: 'App\Repository\PurchasePeriodRepository')]
class PurchasePeriod
{
    #[ORM\Id]
    #[ORM\Column(type: 'bigint', options: ['unsigned' => true])]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\OneToOne(targetEntity: Purchase::class)]
    #[ORM\JoinColumn(name: 'purchase_id', onDelete: 'CASCADE', referencedColumnName: 'id', nullable: false)]
    protected Purchase $purchase;

    #[ORM\Column(type: 'boolean')]
    protected bool $isComplete;

    #[ORM\Column(type: 'datetime')]
    protected DateTime $dateCreated;

    #[ORM\Column(type: 'datetime', nullable: true)]
    protected ?DateTime $dateBegins = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    protected ?DateTime $dateExpires = null;

    /**
     * Constructor
     *
     * @throws Exception
     */
    public function __construct()
    {
        $this->dateCreated = new DateTime();
        $this->isComplete  = false;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Purchase
     */
    public function getPurchase(): Purchase
    {
        return $this->purchase;
    }

    /**
     * @param Purchase $purchase
     *
     * @return self
     */
    public function setPurchase(Purchase $purchase): self
    {
        $this->purchase = $purchase;

        return $this;
    }

    /**
     * @return bool
     */
    public function isComplete(): bool
    {
        return $this->isComplete;
    }

    /**
     * @param bool $isComplete
     *
     * @return self
     */
    public function setIsComplete(bool $isComplete): self
    {
        $this->isComplete = $isComplete;

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
     * @return DateTime|null
     */
    public function getDateBegins(): ?DateTime
    {
        return $this->dateBegins;
    }

    /**
     * @param DateTime $dateBegins
     *
     * @return self
     */
    public function setDateBegins(DateTime $dateBegins): self
    {
        $this->dateBegins = $dateBegins;

        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getDateExpires(): ?DateTime
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
}
