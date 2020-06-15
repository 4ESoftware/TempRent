<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Keyword
 *
 * @ORM\Table(name="keywords")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\KeywordRepository")
 */
class Keyword
{
    public const ACTIVE = 1;

    public const INACTIVE = 0;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @ORM\Column(type="string", name="value")
     */
    private ?string $value = null;

    /**
     * @ORM\Column(type="text", name="description")
     */
    private ?string $description = null;

    /**
     * @ORM\Column(type="integer", name="status")
     */
    private ?int $status = 1;

    public function getId(): int
    {
        return $this->id;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): Keyword
    {
        $this->description = $description;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(?int $status): Keyword
    {
        $this->status = $status;

        return $this;
    }
}
