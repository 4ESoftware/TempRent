<?php

namespace AppBundle\Event;

use AppBundle\Entity\Project;
use Symfony\Component\EventDispatcher\Event;

class ProjectClosed extends Event
{
    const NAME = 'app.project.closed';

    /** @var Project $project */
    private $project;

    public function __construct(Project $project)
    {
        $this->project = $project;
    }

    public function getProject(): Project
    {
        return $this->project;
    }
}
