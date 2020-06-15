<?php

namespace AppBundle\EventListeners;

use AppBundle\Entity\Audit;
use AppBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use FOS\UserBundle\Model\UserManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class LoginListener
{
    private UserManagerInterface $userManager;

    private EntityManager $entityManager;

    private RequestStack $requestStack;

    public function __construct(UserManagerInterface $userManager, EntityManager $entityManager, RequestStack $requestStack){
        $this->userManager = $userManager;
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        /** @var User $user */
        $user = $event->getAuthenticationToken()->getUser();

        $auditEntry = (new Audit())
            ->setType(Audit::LOGIN_EVENT)
            ->setContent(sprintf('User %s logged in at %s', $user->getUsername(), (new \DateTime())->format(DATE_ATOM)))
            ->setUser($user)
            ->setLogTime(new \DateTime())
            ->setIP($_SERVER['REMOTE_ADDR'])
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
}