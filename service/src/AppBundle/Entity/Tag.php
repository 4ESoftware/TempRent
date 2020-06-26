<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Tag
 *
 * @ORM\Table(name="tags")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\TagRepository")
 */
class Tag
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
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="Project", inversedBy="tags")
     * @ORM\JoinColumn(name="project_id", referencedColumnName="id")
     */
    private $project;

    /**
     * @var Keyword
     *
     * @ORM\ManyToOne(targetEntity="Keyword")
     * @ORM\JoinColumn(name="keyword_id", referencedColumnName="id")
     */
    private $keyword;

    /**
     * @ORM\OneToMany(targetEntity="Bid", mappedBy="tag", cascade={"remove"})
     *
     * @var Bid[]|ArrayCollection
     */
    private $bids;

    public function __construct()
    {
        $this->bids = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function setProject(Project $project): self
    {
        $this->project = $project;

        return $this;
    }

    public function getKeyword(): Keyword
    {
        return $this->keyword;
    }

    public function setKeyword(Keyword $keyword): self
    {
        $this->keyword = $keyword;

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
     * @return Tag
     */
    public function setBids($bids): self
    {
        $this->bids = $bids;

        return $this;
    }

    public function addBid(Bid $bid): self
    {
        if (!$this->bids->contains($bid)) {
            $this->bids->add($bid);
        }

        return $this;
    }

    public function getStats()
    {
        $bidders = $this->getBids()->count();

        $files = array_reduce(
            $this->getBids()->toArray(),
            function (int $files, Bid $bid) {
                return $files += $bid->getFiles()->count();
            },
            0
        );;

        return [
            'bidders' => $bidders,
            'files' => $files,
        ];
    }
}

