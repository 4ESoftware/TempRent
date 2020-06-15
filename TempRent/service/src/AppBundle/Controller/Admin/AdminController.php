<?php

namespace AppBundle\Controller\Admin;

use AppBundle\Chatbot\Chatbot;
use AppBundle\Chatbot\Models\Hashtag;
use AppBundle\Entity\Audit;
use AppBundle\Entity\Bid;
use AppBundle\Entity\ConversationLine;
use AppBundle\Entity\Keyword;
use AppBundle\Entity\Project;
use AppBundle\Entity\Tag;
use AppBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends Controller
{
    /**
     * @Route("/admin/keywords", name="admin_view_keywords")
     */
    public function hashtagsAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $keywords = $em->getRepository(Keyword::class)->findAll();

        return $this->render('admin/keywords.html.twig', [
            'keywords' => $keywords,
        ]);
    }

    /**
     * @Route("/admin/projects", name="admin_view_projects")
     */
    public function projectsAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $projects = $em->getRepository(Project::class)->findAll();

        return $this->render('admin/projects.html.twig', [
            'projects' => $projects,
        ]);
    }

    /**
     * @Route("/admin/projects/{id}/conversation", name="view_project_transcript")
     */
    public function viewProjectTranscriptAction(Request $request, Project $project)
    {
        return new JsonResponse([
            'conversation' => array_map(
                function (ConversationLine $cl) {
                    return [
                        'content' => $cl->getContent(),
                        'whom' => $cl->getWhom() ? 'Human' : 'Robot',
                    ];
                },
                $project->getConversation()->toArray()
            ),
        ]);
    }

    /**
     * @Route("/admin/projects/{id}/participants", name="view_project_participants")
     */
    public function viewProjectParticipantsAction(Request $request, Project $project)
    {
        if ($project->getStatus() < Project::PROJECT_WORKFLOW_STEP_7) {
            $tags = array_map(function (Tag $tag) {
                return [
                    'name' => $tag->getKeyword()->getValue(),
                    'bidders' => array_map(function (Bid $bid) {
                        return [
                            'supplier' => $bid->getSupplier()->getFullName(),
                            'company' => $bid->getSupplier()->getCompany()->getName(),
                            'status' => $bid->getStatusAsString(),
                        ];
                    }, $tag->getBids()->toArray()),
                ];
            }, $project->getTags()->toArray());
        } else {
            $tags = array_map(function (Tag $tag) {
                return [
                    'name' => $tag->getKeyword()->getValue(),
                    'bidders' => array_filter(array_map(function (Bid $bid) {
                        if ($bid->getStatus() == Bid::BID_STATUS_ACCEPTED) {
                            return [
                                'supplier' => $bid->getSupplier()->getFullName(),
                                'company' => $bid->getSupplier()->getCompany()->getName(),
                                'status' => $bid->getStatusAsString(),
                            ];
                        }

                        return false;
                    }, $tag->getBids()->toArray()),
                    function ($supplier) {
                        if ($supplier) { return true; }
                        return false;
                    }),
                ];
            }, $project->getTags()->toArray());
        }

        return new JsonResponse($tags);
    }

    /**
     * @Route("/admin/users", name="admin_view_users")
     */
    public function usersAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $users = $em->getRepository(User::class)->findAll();

        $suppliers = [];
        $customers = [];
        $public = [];

        /** @var User $user */
        foreach ($users as $user) {
            switch ($user->getType()) {
                case User::USER_TYPE_PUBLIC:
                    $public[] = $user;
                    break;

                case User::USER_TYPE_SUPPLIER:
                    $suppliers[] = $user;
                    break;

                case User::USER_TYPE_CUSTOMER:
                    $customers[] = $user;
                    break;
            }
        }

        return $this->render('admin/users.html.twig', [
            'public' => $public,
            'suppliers' => $suppliers,
            'customers' => $customers
        ]);
    }

    /**
     * @Route("/admin/users/{id}/log", name="view_user_log")
     */
    public function viewUserLogAction(Request $request, User $user)
    {
        $log = $user->getAuditLog();

        return new JsonResponse(array_map(function (Audit $a) {
            return [
                'user' => $a->getUser()->getUsername(),
                'time' => $a->getLogTime()->format(DATE_ATOM),
                'content' => $a->getContent(),
                'IP' => $a->getIP()
            ];
        }, $log->toArray()));
    }

    /**
     * @Route("/admin/keywords/sync", name="sync_keywords")
     */
    public function syncKeywordsAction()
    {
        $chatbot = new Chatbot();

        /** @var Hashtag[] $chatbotKeywords */
        $chatbotKeywords = $chatbot->getHashtags();

        $keywordMap = [];
        foreach ($chatbotKeywords as $chatbotKeyword) {
            $keywordMap[] = $chatbotKeyword->getHastag();
            $em = $this->getDoctrine()->getManager();
            $kw = $em->getRepository(Keyword::class)->findOneBy(['value' => $chatbotKeyword->getHastag()]);

            if ($kw === null) {
                $kw = (new Keyword())
                    ->setStatus(Keyword::ACTIVE)
                    ->setDescription($chatbotKeyword->getDescription())
                    ->setValue($chatbotKeyword->getHastag());
            } else {
                $kw->setDescription($chatbotKeyword->getDescription());
            }

            $em->persist($kw);
        }

        /** @var Keyword[] $systemKeywords */
        $systemKeywords = $em->getRepository(Keyword::class)->findAll();

        foreach ($systemKeywords as $keyword) {
            if (in_array($keyword->getValue(), $keywordMap)) {
                continue;
            }

            $keyword->setStatus(Keyword::INACTIVE);
            $em->persist($keyword);
        }

        $em->flush();

        return $this->redirectToRoute('admin_view_keywords');
    }
}