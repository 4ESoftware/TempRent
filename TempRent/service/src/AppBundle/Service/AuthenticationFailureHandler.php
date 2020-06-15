<?php

namespace AppBundle\Service;

use AppBundle\Entity\Audit;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\DefaultAuthenticationFailureHandler;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Http\HttpUtils;

class AuthenticationFailureHandler extends DefaultAuthenticationFailureHandler
{
    private EntityManager $entityManager;

    public function __construct(HttpKernelInterface $httpKernel, HttpUtils $httpUtils, EntityManager $entityManager)
    {
        parent::__construct($httpKernel, $httpUtils);

        $this->entityManager = $entityManager;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $session = $request->getSession();

        if(!$session->has('FAILED_AUTHENTICATION_COUNT')) {
            $session->set('FAILED_AUTHENTICATION_COUNT', 0);
        }

        $count = $session->get('FAILED_AUTHENTICATION_COUNT');
        $count++;

        $ip = $_SERVER['REMOTE_ADDR'];
        $username = $request->request->get('_username');

        $audit = (new Audit())
            ->setContent(sprintf('Failed authentication for user: %s', $username))
            ->setLogTime(new \DateTime())
            ->setType(Audit::FAILED_LOGIN_EVENT)
            ->setIP($ip)
        ;

        $this->entityManager->persist($audit);
        $this->entityManager->flush();

        if ($count > 3) {
            dump('yeaah');
        }

        $session->set('LAST_FAILED_AUTHENTICATION_TIMESTAMP', time());
        $session->set('FAILED_AUTHENTICATION_COUNT', $count);

        return $this->httpUtils->createRedirectResponse($request, $this->options['login_path']);

    }
}