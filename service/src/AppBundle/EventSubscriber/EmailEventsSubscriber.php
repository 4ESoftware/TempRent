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
    /** @var UrlGeneratorInterface $router */
    private $router;

    /** @var RequestStack $requestStack */
    private $requestStack;

    /** @var \Swift_Mailer $mailer */
    private $mailer;

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
            ->setFrom('no-reply@temprent.4e.ro')
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
                    ->setFrom('no-reply@temprent.4e.ro')
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
        // nothing at the moment, please insert here logic for sending emails when bids are rejected.
        // place the templates in service/app/Resources/views/emails folder
        // naming convention (you can ignore it, ofc):
        // service/app/Resources/views/emails/<entity>/<eventname>/<template_name>.html.twig
    }

    public function onBidAccepted(BidAccepted $event)
    {
        // nothing at the moment, please insert here logic for sending emails when bids are accepted.
        // place the templates in service/app/Resources/views/emails folder
        // naming convention (you can ignore it, ofc):
        // service/app/Resources/views/emails/<entity>/<eventname>/<template_name>.html.twig
    }
}