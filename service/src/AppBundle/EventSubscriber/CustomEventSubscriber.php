<?php

namespace AppBundle\EventSubscriber;

use AppBundle\Entity\Audit;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use FOS\UserBundle\Event\FormEvent;
use FOS\UserBundle\FOSUserEvents;
use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;
use GeoIp2\Database\Reader;

class CustomEventSubscriber implements EventSubscriberInterface
{
    /** @var UrlGeneratorInterface $router */
    private $router;

    /** @var RequestStack $requestStack */
    private $requestStack;

    /** @var UserManagerInterface $userManager */
    private $userManager;

    /** @var EntityManager $entityManager */
    private $entityManager;

    /** @var string $geoIPdir */
    private $geoIPdir;

    public function __construct(
        UrlGeneratorInterface $router,
        RequestStack $requestStack,
        UserManagerInterface $userManager,
        EntityManager $entityManager,
        string $geoIPdir
    ) {
        $this->router = $router;
        $this->userManager = $userManager;
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
        $this->geoIPdir = $geoIPdir;
    }

    public static function getSubscribedEvents()
    {
        return [
            FOSUserEvents::REGISTRATION_SUCCESS => 'onRegistrationCompleted',
            KernelEvents::REQUEST => 'onRequestValidateLoginFails',
            SecurityEvents::INTERACTIVE_LOGIN => 'onSecurityInteractiveLogin'
        ];
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        $ip = $_SERVER['REMOTE_ADDR'];

        /** @var User $user */
        $user = $event->getAuthenticationToken()->getUser();
        $reader = new Reader($this->geoIPdir.'/GeoLite2-City.mmdb');
        $record = $reader->city($ip);

        $auditEntry = (new Audit())
            ->setCity($record->city->name)
            ->setCountry($record->country->name)
            ->setType(Audit::LOGIN_EVENT)
            ->setContent(sprintf('User %s loggedin at %s', $user->getUsername(), (new \DateTime())->format(DATE_ATOM)))
            ->setUser($user)
            ->setLogTime(new \DateTime())
            ->setIP($ip)
        ;

        $user->addAuditLog($auditEntry);

        $this->entityManager->persist($auditEntry);
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $session = $this->requestStack->getCurrentRequest()->getSession();
        $session->remove('LAST_FAILED_AUTHENTICATION_TIMESTAMP');
        $session->remove('FAILED_AUTHENTICATION_COUNT');

        return;
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