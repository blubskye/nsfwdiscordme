<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use DateTime;
use Exception;

/**
 * @ORM\Table(name="purchase_period", indexes={
 *      @ORM\Index(columns={"is_complete", "date_expires"})
 * })
 * @ORM\Entity(repositoryClass="App\Repository\PurchasePeriodRepository")
 */
class PurchasePeriod
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\Column(type="bigint", options={"unsigned"=true})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Purchase
     * @ORM\OneToOne(targetEntity="Purchase")
     * @ORM\JoinColumn(name="purchase_id", onDelete="CASCADE", referencedColumnName="id", nullable=false)
     */
    protected $purchase;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    protected $isComplete;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime")
     */
    protected $dateCreated;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $dateBegins;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $dateExpires;

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
     * @return PurchasePeriod
     */
    public function setPurchase(Purchase $purchase): PurchasePeriod
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
     * @return PurchasePeriod
     */
    public function setIsComplete(bool $isComplete): PurchasePeriod
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
     * @return PurchasePeriod
     */
    public function setDateCreated(DateTime $dateCreated): PurchasePeriod
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
     * @return PurchasePeriod
     */
    public function setDateBegins(DateTime $dateBegins): PurchasePeriod
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
     * @return PurchasePeriod
     */
    public function setDateExpires(DateTime $dateExpires): PurchasePeriod
    {
        $this->dateExpires = $dateExpires;

        return $this;
    }
}
