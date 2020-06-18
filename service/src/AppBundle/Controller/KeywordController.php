<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Keyword;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class KeywordController extends Controller
{
    /**
     * @Route("/profile/keywords", name="view_keywords")
     * @Security("has_role('ROLE_SUPPLIER')")
     */
    public function viewKeywordsAction(Request $request, UserInterface $user)
    {
        $em = $this->getDoctrine()->getManager();

        $keywords = $em->getRepository('AppBundle:Keyword')->findAll();
        /** @var ArrayCollection $userKeywords */
        $userKeywords = $user->getKeywords();
        $render = [];

        foreach ($keywords as $keyword) {
            $temp = [
                'keyword' => $keyword,
                'selected' => false,
            ];
            if ($userKeywords->contains($keyword)) {
                $temp['selected'] = true;
            }

            $render[] = $temp;
        }

        return $this->render('default/profile/keywords/view.html.twig', [
            'keywords' => $render,
            'user' => $user
        ]);
    }

    /**
     * @Route("/profile/keywords/filter", name="filter_keywords")
     * @Security("has_role('ROLE_SUPPLIER')")
     */
    public function filterKeywordsAction(Request $request, UserInterface $user)
    {
        $em = $this->getDoctrine()->getManager();

        $text = $request->request->get('text');
        if ($text !== '') {
//            $keywords = $em->getRepository('AppBundle:Keyword')->findByLikeKeyword($text);

            $keywords = $em->getRepository('AppBundle:Keyword')->createQueryBuilder('k')
                ->where('k.value LIKE :text')
                ->setParameter('text', '%' . $text . '%')
                ->getQuery()
                ->getResult();
        } else {
            $keywords = $em->getRepository('AppBundle:Keyword')->findAll();
        }

        /** @var ArrayCollection $userKeywords */
        $userKeywords = $user->getKeywords();
        $render = [];

        foreach ($keywords as $keyword) {
            $temp = [
                'keyword' => [
                    'id' => $keyword->getId(),
                    'value' => $keyword->getValue(),
                ],
                'selected' => false,
            ];
            if ($userKeywords->contains($keyword)) {
                $temp['selected'] = true;
            }

            $render[] = $temp;
        }

        return new JsonResponse([
            'keywords' => $render,
        ]);
    }

    /**
     * @Route("/profile/keywords/add/{id}", name="add_keyword")
     * @Security("has_role('ROLE_SUPPLIER')")
     * @param Request $request
     * @param UserInterface $user
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function addKeywordToUser(Request $request, UserInterface $user, Keyword $keyword)
    {
        $em = $this->getDoctrine()->getManager();

        $user->addKeyword($keyword);

        $em->persist($user);
        $em->flush();

        return $this->redirectToRoute('view_keywords');
    }

    /**
     * @Route("/profile/keywords/remove/{id}", name="remove_keyword")
     * @Security("has_role('ROLE_SUPPLIER')")
     * @param Request $request
     * @param UserInterface $user
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function removeKeywordToUser(Request $request, UserInterface $user, Keyword $keyword)
    {
        $em = $this->getDoctrine()->getManager();

        $user->removeKeyword($keyword);

        $em->persist($user);
        $em->flush();

        return $this->redirectToRoute('view_keywords');
    }
}
