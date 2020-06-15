<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity
 * @ORM\Table(name="users")
 */
class User extends BaseUser
{
    public const USER_TYPE_PUBLIC = 0;
    public const USER_TYPE_SUPPLIER = 1;
    public const USER_TYPE_CUSTOMER = 2;
    public const USER_TYPE_ADMIN = 4;

    public static array $allowedUserTypes = [
        self::USER_TYPE_PUBLIC,
        self::USER_TYPE_SUPPLIER,
        self::USER_TYPE_CUSTOMER,
        self::USER_TYPE_ADMIN,
    ];

    private static array $roleMap = [
        self::USER_TYPE_PUBLIC => 'ROLE_USER',
        self::USER_TYPE_SUPPLIER => 'ROLE_SUPPLIER',
        self::USER_TYPE_CUSTOMER => 'ROLE_CUSTOMER',
    ];

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\Column(type="integer", name="type")
     */
    private ?int $type = 0;

    /**
     * @ORM\Column(type="string", name="full_name", nullable=true)
     *
     * @Assert\NotBlank(message="Please enter your name.", groups={"Registration", "Profile"})
     * @Assert\Length(
     *     min=3,
     *     max=255,
     *     minMessage="The name is too short.",
     *     maxMessage="The name is too long.",
     *     groups={"Registration", "Profile"}
     * )
     */
    private ?string $fullName = null;

    /**
     * @ORM\OneToOne(targetEntity="Company", mappedBy="user", cascade={"remove"})
     */
    private ?Company $company = null;

    /**
     * @ORM\Column(name="facebook_id", type="string", length=255, nullable=true)
     */
    protected ?string $facebook_id = null;

    /**
     * @ORM\Column(name="facebook_access_token", type="string", length=255, nullable=true)
     */
    protected ?string $facebook_access_token = null;

    /**
     * @ORM\Column(name="google_id", type="string", length=255, nullable=true)
     */
    protected ?string $google_id = null;

    /**
     * @ORM\Column(name="google_access_token", type="string", length=255, nullable=true)
     */
    protected ?string $google_access_token = null;

    /**
     * @ORM\ManyToMany(targetEntity="Keyword")
     * @ORM\JoinTable(name="user_keywords",
     *     joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="keyword_id", referencedColumnName="id")}
     * )
     * @var ArrayCollection|Keyword[]
     */
    private $keywords;

    /**
     * @ORM\OneToMany(targetEntity="Project", mappedBy="creator", cascade={"remove"})
     *
     * @var Project[]|ArrayCollection
     */
    private $projects;

    /**
     * @ORM\OneToMany(targetEntity="Bid", mappedBy="supplier", cascade={"remove"})
     *
     * @var Bid[]|ArrayCollection
     */
    private $bids;

    /**
     * @ORM\OneToMany(targetEntity="Audit", mappedBy="user", cascade={"remove"})
     *
     * @var Audit[]|ArrayCollection
     */
    private $auditLog = null;

    public function __construct()
    {
        parent::__construct();

        $this->keywords = new ArrayCollection();
        $this->projects = new ArrayCollection();
        $this->bids = new ArrayCollection();
        $this->auditLog = new ArrayCollection();
    }

    public function getType(): int
    {
        return $this->type;
    }

    public function setType(int $type): self
    {
        if (in_array($type, self::$allowedUserTypes)) {
            $this->type = $type;
            $this->addRole(self::$roleMap[$this->type]);
        }

        return $this;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(?string $fullName): self
    {
        $this->fullName = $fullName;

        return $this;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(Company $company): self
    {
        $this->company = $company;

        return $this;
    }

    public function getFacebookId(): ?string
    {
        return $this->facebook_id;
    }

    public function setFacebookId(?string $facebook_id): User
    {
        $this->facebook_id = $facebook_id;
        return $this;
    }

    public function getFacebookAccessToken(): ?string
    {
        return $this->facebook_access_token;
    }

    public function setFacebookAccessToken(?string $facebook_access_token): User
    {
        $this->facebook_access_token = $facebook_access_token;
        return $this;
    }

    public function getGoogleId(): ?string
    {
        return $this->google_id;
    }

    public function setGoogleId(?string $google_id): User
    {
        $this->google_id = $google_id;
        return $this;
    }

    public function getGoogleAccessToken(): ?string
    {
        return $this->google_access_token;
    }

    public function setGoogleAccessToken(?string $google_access_token): User
    {
        $this->google_access_token = $google_access_token;

        return $this;
    }

    /**
     * @return Keyword[]|ArrayCollection
     */
    public function getKeywords()
    {
        return $this->keywords;
    }

    /**
     * @param Keyword[]|ArrayCollection $keywords
     * @return User
     */
    public function setKeywords($keywords): self
    {
        $this->keywords = $keywords;

        return $this;
    }

    public function addKeyword(Keyword $keyword): self
    {
        if (!$this->keywords->contains($keyword)) {
            $this->keywords->add($keyword);
        }

        return $this;
    }

    public function removeKeyword(Keyword $keyword): self
    {
        if ($this->keywords->contains($keyword)) {
            $this->keywords->removeElement($keyword);
        }

        return $this;
    }

    /**
     * @return Project[]|ArrayCollection
     */
    public function getProjects()
    {
        return $this->projects;
    }

    /**
     * @param Project[]|ArrayCollection $projects
     * @return User
     */
    public function setProjects($projects)
    {
        $this->projects = $projects;

        return $this;
    }

    /**
     * @return Bid[]|ArrayCollection
     */
    public function getBids()
    {
        return $this->bids;
    }

    /**
     * @param Bid[]|ArrayCollection $bids
     * @return User
     */
    public function setBids($bids)
    {
        $this->bids = $bids;

        return $this;
    }

    /**
     * @return Audit[]|ArrayCollection
     */
    public function getAuditLog()
    {
        return $this->auditLog;
    }

    /**
     * @param Audit[]|ArrayCollection $auditLog
     * @return User
     */
    public function setAuditLog($auditLog)
    {
        $this->auditLog = $auditLog;

        return $this;
    }

    public function addAuditLog(Audit $audit): self
    {
        $this->auditLog->add($audit);

        return $this;
    }
}
