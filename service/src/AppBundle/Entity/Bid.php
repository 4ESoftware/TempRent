<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Bid
 *
 * @ORM\Table(name="bids")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\BidRepository")
 */
class Bid
{
    public const BID_STATUS_PENDING = 1;
    public const BID_STATUS_ACCEPTED = 2;
    public const BID_STATUS_REJECTED = 3;

    public static $validStatuses = [
        self::BID_STATUS_PENDING,
        self::BID_STATUS_ACCEPTED,
        self::BID_STATUS_REJECTED,
    ];

    private static $statusLabels = [
        self::BID_STATUS_PENDING => 'PENDING',
        self::BID_STATUS_ACCEPTED => 'ACCEPTED',
        self::BID_STATUS_REJECTED => 'REJECTED',
    ];

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="integer")
     */
    private $status = self::BID_STATUS_PENDING;

    /**
     * @var string|null
     *
     * @ORM\Column(name="note", type="text")
     */
    private $note = null;

    /**
     * @var int|null
     *
     * @ORM\Column(name="price", type="integer", nullable=true)
     */
    private $price = null;

    /**
     * @var Tag|null
     *
     * @ORM\ManyToOne(targetEntity="Tag", inversedBy="bids")
     * @ORM\JoinColumn(name="tag_id", referencedColumnName="id")
     */
    private $tag = null;

    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="User", inversedBy="bids")
     * @ORM\JoinColumn(name="supplier", referencedColumnName="id")
     */
    private $supplier = null;

    /**
     * @ORM\OneToMany(targetEntity="File", mappedBy="bid", cascade={"remove"})
     *
     * @var File[]|ArrayCollection
     */
    private $files;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime", name="created_at", nullable=true)
     */
    private $createdAt = null;

    public function __construct()
    {
        $this->files = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setStatus($status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setNote(string $note): self
    {
        $this->note = $note;

        return $this;
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function getTag(): ?Tag
    {
        return $this->tag;
    }

    public function setTag(Tag $tag): self
    {
        $this->tag = $tag;

        return $this;
    }

    public function getSupplier(): ?User
    {
        return $this->supplier;
    }

    public function setSupplier(User $supplier): self
    {
        $this->supplier = $supplier;

        return $this;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(int $price): Bid
    {
        $this->price = $price;

        return $this;
    }

    /**
     * @return File[]|ArrayCollection
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * @param ArrayCollection|File[] $files
     * @return Bid
     */
    public function setFiles($files): self
    {
        $this->files = $files;

        return $this;
    }

    public function addFile(File $file): self
    {
        if (!$this->files->contains($file)) {
            $this->files->add($file);
        }

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTime $createdAt): Bid
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getStatusAsString(): string
    {
        return self::$statusLabels[$this->getStatus()];
    }
}

