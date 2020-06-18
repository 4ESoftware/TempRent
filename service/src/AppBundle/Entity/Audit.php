<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Audit
 *
 * @ORM\Table(name="audit")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\AuditRepository")
 */
class Audit
{
    const LOGIN_EVENT = 1;
    const FAILED_LOGIN_EVENT = 2;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="logTime", type="datetime")
     */
    private $logTime;

    /**
     * @var int
     *
     * @ORM\Column(name="type", type="integer")
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text")
     */
    private $content;

    /**
     * @var User|null
     *
     * @ORM\ManyToOne(targetEntity="User", inversedBy="auditLog")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user = null;

    /**
     * @var string|null
     *
     * @ORM\Column(name="ip", type="string")
     */
    private $IP = null;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set logTime.
     *
     * @param \DateTime $logTime
     *
     * @return Audit
     */
    public function setLogTime($logTime)
    {
        $this->logTime = $logTime;

        return $this;
    }

    /**
     * Get logTime.
     *
     * @return \DateTime
     */
    public function getLogTime()
    {
        return $this->logTime;
    }

    /**
     * Set type.
     *
     * @param int $type
     *
     * @return Audit
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set content.
     *
     * @param string $content
     *
     * @return Audit
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * @param User|null $user
     * @return Audit
     */
    public function setUser(?User $user): Audit
    {
        $this->user = $user;

        return $this;
    }

    public function getIP(): string
    {
        return $this->IP;
    }

    public function setIP(string $IP): Audit
    {
        $this->IP = $IP;

        return $this;
    }
}
