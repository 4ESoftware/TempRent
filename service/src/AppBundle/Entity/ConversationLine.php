<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ConversationLine
 *
 * @ORM\Table(name="conversation_lines")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ConversationLineRepository")
 */
class ConversationLine
{
    public const HUMAN = 1;
    public const ROBOT = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text")
     */
    private $content;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="spokenAt", type="datetime")
     */
    private $spokenAt;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", name="whom")
     */
    private $whom = null;

    /**
     * @var Project
     *
     * @ORM\ManyToOne(targetEntity="Project", inversedBy="conversation", cascade={"persist"})
     * @ORM\JoinColumn(name="project_id", referencedColumnName="id")
     */
    private $project;

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
     * Set content.
     *
     * @param string $content
     *
     * @return ConversationLine
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
     * Set spokenAt.
     *
     * @param \DateTime $spokenAt
     *
     * @return ConversationLine
     */
    public function setSpokenAt($spokenAt)
    {
        $this->spokenAt = $spokenAt;

        return $this;
    }

    /**
     * Get spokenAt.
     *
     * @return \DateTime
     */
    public function getSpokenAt()
    {
        return $this->spokenAt;
    }

    /**
     * @return int|null
     */
    public function getWhom(): ?int
    {
        return $this->whom;
    }

    /**
     * @param int|null $whom
     * @return ConversationLine
     */
    public function setWhom(?int $whom): ConversationLine
    {
        $this->whom = $whom;
        return $this;
    }

    /**
     * @return Project
     */
    public function getProject(): Project
    {
        return $this->project;
    }

    /**
     * @param Project $project
     * @return ConversationLine
     */
    public function setProject(Project $project): ConversationLine
    {
        $this->project = $project;
        return $this;
    }
}
