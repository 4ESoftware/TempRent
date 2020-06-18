<?php

namespace AppBundle\EventSubscriber;

use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\FOSUserEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CustomEventSubscriber implements EventSubscriberInterface
{
    /** @var UrlGeneratorInterface $router */
    private $router;

    /** @var RequestStack $requestStack */
    private $requestStack;

    public function __construct(UrlGeneratorInterface $router, RequestStack $requestStack)
    {
        $this->router = $router;
        $this->requestStack = $requestStack;
    }

    public static function getSubscribedEvents()
    {
        return [
            FOSUserEvents::REGISTRATION_SUCCESS => 'onRegistrationCompleted',
            KernelEvents::REQUEST => 'onRequestValidateLoginFails',
        ];
    }

    public function onRegistrationCompleted(FormEvent $event)
    {
        $url = $this->router->generate('fos_user_profile_show');
        $event->setResponse(new RedirectResponse($url));
    }

    public function onRequestValidateLoginFails(GetResponseEvent $event)
    {
        $session = $this->requestStack->getCurrentRequest()->getSession();
        if (!$session->has('FAILED_AUTHENTICATION_COUNT')) {
            return;
        }

        if ($session->get('FAILED_AUTHENTICATION_COUNT') > 3
            && ($session->get('LAST_FAILED_AUTHENTICATION_TIMESTAMP') > time() - 300)
        ) {
            $response = (new Response())
                ->setStatusCode(503)
                ->setContent('Too many failed login attempts.');

            $event->setResponse($response);
        }

        return;
    }
}