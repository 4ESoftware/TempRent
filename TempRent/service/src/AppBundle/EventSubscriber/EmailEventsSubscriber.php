<?php

namespace AppBundle\EventSubscriber;

use AppBundle\Event\BidAccepted;
use AppBundle\Event\BidRejected;
use AppBundle\Event\ProjectClosed;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Templating\EngineInterface;

class EmailEventsSubscriber implements EventSubscriberInterface
{
    private UrlGeneratorInterface $router;

    private RequestStack $requestStack;

    private \Swift_Mailer $mailer;

    private $templating;

    public function __construct(UrlGeneratorInterface $router, RequestStack $requestStack, \Swift_Mailer $mailer, EngineInterface $templating)
    {
        $this->router = $router;
        $this->requestStack = $requestStack;
        $this->mailer = $mailer;
        $this->templating = $templating;
    }

    public static function getSubscribedEvents()
    {
        return [
            ProjectClosed::NAME => 'onProjectClosed',
            BidRejected::NAME => 'onBidRejected',
            BidAccepted::NAME => 'onBidAccepted',
        ];
    }

    public function onProjectClosed(ProjectClosed $event)
    {
        $project = $event->getProject();

        $message = (new \Swift_Message('Project: ' . $project->getName() . ' is closed.'))
            ->setFrom('no-reply@temprent.com')
            ->setTo($project->getCreator()->getEmail())
            ->setBody(
                $this->templating->render(
                    'emails/project/closed/customer.html.twig',
                    [
                        'name' => $project->getCreator()->getFullName(),
                        'project' => $project,
                    ]
                ),
                'text/html'
            )
        ;
        $this->mailer->send($message);

        foreach ($project->getTags() as $tag) {
            foreach ($tag->getBids() as $bid) {
                $message = (new \Swift_Message('Project: ' . $project->getName() . ' is closed.'))
                    ->setFrom('no-reply@temprent.com')
                    ->setTo($bid->getSupplier()->getEmail())
                    ->setBody(
                        $this->templating->render(
                            'emails/project/closed/bidders.html.twig',
                            [
                                'name' => $bid->getSupplier()->getFullName(),
                                'project' => $project,
                                'bid' => $bid,
                            ]
                        ),
                        'text/html'
                    )
                ;

                $this->mailer->send($message);
            }
        }
    }

    public function onBidRejected(BidRejected $event)
    {
    }

    public function onBidAccepted(BidAccepted $event)
    {
    }
}