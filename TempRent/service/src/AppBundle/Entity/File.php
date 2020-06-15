<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * File
 *
 * @ORM\Table(name="files")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\FileRepository")
 */
class File
{
    public const FILE_TYPE_CONTRACT= 1;
    public const FILE_TYPE_OFFER = 2;
    public const FILE_TYPE_OTHER = 3;

    public static array $validFileTypes = [
        self::FILE_TYPE_CONTRACT,
        self::FILE_TYPE_OFFER,
        self::FILE_TYPE_OTHER,
    ];

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @ORM\Column(type="string", name="filename")
     */
    private string $filename;

    /**
     * @ORM\Column(type="integer", name="type")
     */
    private int $type;

    /**
     * @ORM\Column(type="datetime", name="uploaded_at", nullable=true)
     */
    private ?\DateTime $uploadedAt = null;

    /**
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(name="creator", referencedColumnName="id")
     */
    private User $creator;

    /**
     * @ORM\ManyToOne(targetEntity="Bid", inversedBy="files")
     * @ORM\JoinColumn(name="bid_id", referencedColumnName="id")
     */
    private Bid $bid;


    public function getId(): int
    {
        return $this->id;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): self
    {
        $this->filename = $filename;

        return $this;
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function setType(int $type): self
    {
        if (in_array($type, self::$validFileTypes)) {
            $this->type = $type;
        }

        return $this;
    }

    public function getUploadedAt(): ?\DateTime
    {
        return $this->uploadedAt;
    }

    public function setUploadedAt(?\DateTime $uploadedAt): File
    {
        $this->uploadedAt = $uploadedAt;

        return $this;
    }

    public function getCreator(): User
    {
        return $this->creator;
    }

    public function setCreator(User $creator): self
    {
        $this->creator = $creator;

        return $this;
    }

    public function getBid(): Bid
    {
        return $this->bid;
    }

    public function setBid(Bid $bid): self
    {
        $this->bid = $bid;

        return $this;
    }
}

