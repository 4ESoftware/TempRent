<?php

namespace AppBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Project
 *
 * @ORM\Table(name="projects")
 * @ORM\Entity(repositoryClass="AppBundle\Repository\ProjectRepository")
 */
class Project
{
    public const PROJECT_WORKFLOW_STEP_1 = 1;
    public const PROJECT_WORKFLOW_STEP_2 = 2;
    public const PROJECT_WORKFLOW_STEP_3 = 3;
    public const PROJECT_WORKFLOW_STEP_4 = 4;
    public const PROJECT_WORKFLOW_STEP_5 = 5;
    public const PROJECT_WORKFLOW_STEP_6 = 6;
    public const PROJECT_WORKFLOW_STEP_7 = 7;

    public const PROJECT_WORKFLOW_LABELS = [
        self::PROJECT_WORKFLOW_STEP_1 => 'Conversation',
        self::PROJECT_WORKFLOW_STEP_2 => 'Tags',
        self::PROJECT_WORKFLOW_STEP_3 => 'New project submitted',
        self::PROJECT_WORKFLOW_STEP_4 => 'Wait for all tags to complete',
        self::PROJECT_WORKFLOW_STEP_5 => 'Approve each Supplier',
        self::PROJECT_WORKFLOW_STEP_6 => 'Upload additional information (bank letter) after receiving approval',
        self::PROJECT_WORKFLOW_STEP_7 => 'Closed',
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
     * @var string|null
     *
     * @ORM\Column(type="string", name="name")
     */
    private $name = null;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", name="draft_labels", nullable=true)
     */
    private $draftLabels = null;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="User", inversedBy="projects")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $creator = null;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime", name="created_at")
     */
    private $createdAt = null;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", name="status")
     */
    private $status = Project::PROJECT_WORKFLOW_STEP_1;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", name="conversation_id", nullable=true)
     */
    private $conversationId = null;

    /**
     * @ORM\OneToMany(targetEntity="ConversationLine", mappedBy="project", cascade={"remove"})
     *
     * @var ConversationLine[]|ArrayCollection
     */
    private $conversation;

    /**
     * @ORM\OneToMany(targetEntity="Tag", mappedBy="project", cascade={"remove"})
     *
     * @var Tag[]|ArrayCollection
     */
    private $tags;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
        $this->conversation = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): Project
    {
        $this->name = $name;

        return $this;
    }

    public function getCreator(): ?User
    {
        return $this->creator;
    }

    public function setCreator(User $creator): Project
    {
        $this->creator = $creator;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): Project
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(?int $status): Project
    {
        if ($status == null) {
            $status = self::PROJECT_WORKFLOW_STEP_1;
        }

        $this->status = $status;

        return $this;
    }

    public function getDraftLabels(): ?string
    {
        return $this->draftLabels;
    }

    public function setDraftLabels(?string $draftLabels): Project
    {
        $this->draftLabels = $draftLabels;

        return $this;
    }

    public function addDraftLabel(?string $label) {
        if ($label !== null) {

            $arr = [];
            if ($this->draftLabels !== null) {
                $arr = explode('|', $this->draftLabels);
            }

            $arr[] = $label;

            $this->draftLabels = implode('|', $arr);
        }

        return $this;
    }

    /**
     * @return ConversationLine[]|ArrayCollection
     */
    public function getConversation()
    {
        return $this->conversation;
    }

    /**
     * @param ConversationLine[]|ArrayCollection $conversationLines
     * @return Project
     */
    public function setConversation($conversationLines)
    {
        $this->conversation = $conversationLines;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getConversationId()
    {
        return $this->conversationId;
    }

    /**
     * @param int $conversationId
     * @return Project
     */
    public function setConversationId(?int $conversationId): Project
    {
        $this->conversationId = $conversationId;

        return $this;
    }

    public function addConversationLine(ConversationLine $conversationLine): self
    {
        $this->conversation->add($conversationLine);

        return $this;
    }

    public function getTranscriptAsArray(): array
    {
        return array_map(function (ConversationLine $cl) {
            return $cl->getContent();
        }, $this->conversation->toArray());
    }

    /**
     * @return Tag[]|ArrayCollection
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @param Tag[]|ArrayCollection $tags
     * @return Project
     */
    public function setTags($tags)
    {
        $this->tags = $tags;

        return $this;
    }

    public function addTag(Tag $tag)
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
        }

        return $this;
    }

    public function canClose()
    {
        $tagStatuses = array_map(function (Tag $tag) {
                $accepted = count(array_filter(
                        $tag->getBids()->toArray(),
                        function (Bid $bid) {
                            return $bid->getStatus() == Bid::BID_STATUS_ACCEPTED;
                        }
                    )
                );

                return $accepted ? 1 : 0;
            },
            $this->getTags()->toArray()
        );

        return array_sum($tagStatuses) == count($tagStatuses);
    }

    public function getWinners()
    {
        $filtered = [];

        foreach ($this->getTags() as $tag) {
            foreach ($tag->getBids() as $bid) {
                if ($bid->getStatus() === Bid::BID_STATUS_ACCEPTED) {
                    $filtered[] = $bid;
                }
            }
        }

        return $filtered;
    }

    public function getStats()
    {
        if ($this->getStatus() < self::PROJECT_WORKFLOW_STEP_3) {
            return [
                'bidders' => 'Not Available',
                'completion' => 'Not Available',
                'files' => 'Not Available'
            ];
        }

        $bidders = array_reduce(
            $this->getTags()->toArray(),
            function (int $bidders, Tag $tag) {
                return $bidders += $tag->getBids()->count();
            },
            0
        );

        $files = array_reduce(
            $this->getTags()->toArray(),
            function (int $files, Tag $tag) {
                return $files += array_reduce(
                    $tag->getBids()->toArray(),
                    function (int $files, Bid $bid) {
                        return $files += $bid->getFiles()->count();
                    },
                    0
                );
            },
            0
        );

        $completion = round(
            array_reduce(
                array_map(
                    function (Tag $tag) {
                        return $tag->getBids()->count() ? 1 : 0;
                    },
                    $this->getTags()->toArray()
                ),
                function (int $total, int $hasBids) {
                    return $total += $hasBids;
                },
                0
            ) * 100 / $this->getTags()->count(),
            2
        );

        return [
            'bidders' => $bidders,
            'completion' => $completion,
            'files' => $files
        ];
    }

    public function getStatusAsString(): string
    {
        return self::PROJECT_WORKFLOW_LABELS[$this->getStatus()];
    }
}

