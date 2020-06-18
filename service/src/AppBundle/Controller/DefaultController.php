<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Keyword;
use AppBundle\Entity\Project;
use AppBundle\Entity\Tag;
use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request, UserInterface $user = null)
    {
        return $this->redirectToRoute('dashboard');
    }

    /**
     * @Route("/dashboard", name="dashboard")
     */
    public function dashboardAction(Request $request, UserInterface $user)
    {
        if ($this->get('security.authorization_checker')->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('admin_view_keywords');
        }

        $tags = $this->getDoctrine()->getManager()->getRepository(Keyword::class)->findBy(['status' => 1]);

        if ($user->getType() == User::USER_TYPE_SUPPLIER) {
            $tags = $user->getKeywords();
        }

        return $this->render('default/index.html.twig', [
            'tags' => $tags,
            'user' => $user
        ]);
    }

    /**
     * @Route("/dashboard/filter", name="dashboard_filter")
     */
    public function filterDashboardAction(Request $request, UserInterface $user)
    {
        $em = $this->getDoctrine()->getManager();
        $tags = $request->request->get('selection');

        if ($tags == null && $user->getType() == User::USER_TYPE_SUPPLIER) {
            $tags = array_map(function (Keyword $kw) {
                    return $kw->getId();
                }, $user->getKeywords()->toArray()
            );
        } elseif ($tags == null) {
            $tags = array_map(function (Keyword $kw) {
                    return $kw->getId();
                }, $this->getDoctrine()->getManager()->getRepository(Keyword::class)->findBy(['status' => 1])
            );
        }

        /** @var Project[] $projects */
        $projects = $em->getRepository(Project::class)->findByKeywordIds($tags);

        $toReturn = [];
        foreach ($projects as $project) {
            $toReturn[] = [
                'id' => $project->getId(),
                'name' => $project->getName(),
                'creator' => [
                    'id' => $project->getCreator()->getId(),
                    'name' => $project->getCreator()->getFullName(),
                ],
                'tags' => array_map(function (Tag $tag) {
                        return [
                            'id' => $tag->getId(),
                            'keyword' => [
                                'id' => $tag->getKeyword()->getId(),
                                'value' => $tag->getKeyword()->getValue(),
                            ],
                        ];
                    },
                    $project->getTags()->toArray()
                ),
                'stats' => $project->getStats(),
            ];
        }

        return new JsonResponse(['projects' => $toReturn]);
    }

    /**
     * @Route("/user/{id}", name="view_user_profile")
     */
    public function viewUserProfileAction(Request $request, User $user)
    {
        return $this->render('default/user-profile.html.twig', [
            'user' => $user,
        ]);
    }


    /**
     * @Route("/privacy", name="view_privacy")
     */
    public function viewPrivacyAction()
    {
        return $this->render('default/privacy.html.twig', []);
    }

    /**
     * @Route("/user/{id}/delete", name="delete_user")
     */
    public function deleteUserAction(Request $request, User $user, UserInterface $loggedUser)
    {
        if($loggedUser->getId() != $user->getId()) {
            return $this->redirectToRoute('dashboard');
        }

        $em = $this->getDoctrine()->getManager();

        $em->remove($user);
        $em->flush();

        return $this->redirectToRoute('fos_user_security_logout');
    }
}
