<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Company
 *
 * @ORM\Table(name="companies")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\CompanyRepository")
 */
class Company
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", name="cui")
     */
    private $cui = null;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", name="name")
     */
    private $name = null;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", name="address")
     */
    private $address = null;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", name="phone")
     */
    private $phone = null;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", name="iban")
     */
    private $iban = null;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", name="bank")
     */
    private $bank = null;

    /**
     * @var User|null
     *
     * @ORM\OneToOne(targetEntity="User", inversedBy="company")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user = null;

    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getCui(): ?string
    {
        return $this->cui;
    }

    /**
     * @param string $cui
     * @return Company
     */
    public function setCui(string $cui): Company
    {
        $this->cui = $cui;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function getIban(): ?string
    {
        return $this->iban;
    }

    public function getAnonIban(): ?string
    {
        return substr($this->iban, 0, 5).'****'.substr($this->iban, -4);
    }

    public function setIban(string $iban): self
    {
        $this->iban = $iban;

        return $this;
    }

    public function getBank(): ?string
    {
        return $this->bank;
    }

    public function setBank(string $bank): self
    {
        $this->bank = $bank;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): Company
    {
        $this->user = $user;

        return $this;
    }
}
