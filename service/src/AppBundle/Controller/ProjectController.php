<?php

namespace AppBundle\Controller;

use AppBundle\Chatbot\Chatbot;
use AppBundle\Entity\Bid;
use AppBundle\Entity\ConversationLine;
use AppBundle\Entity\File;
use AppBundle\Entity\Keyword;
use AppBundle\Entity\Project;
use AppBundle\Entity\Tag;
use AppBundle\Entity\User;
use AppBundle\Event\BidAccepted;
use AppBundle\Event\BidRejected;
use AppBundle\Event\ProjectClosed;
use AppBundle\Form\AddFilesBidType;
use AppBundle\Form\BidType;
use AppBundle\Form\EditBidType;
use AppBundle\Service\FileUploader;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;
use Symfony\Component\Routing\Annotation\Route;

class ProjectController extends Controller
{
    /** @var Chatbot $chatbot */
    private $chatbot;

    public function __construct(Chatbot $chatbot)
    {
        $this->chatbot = $chatbot;
    }

    /**
     * @Route("/project/add", name="project_add")
     */
    public function addProjectAction(Request $request, UserInterface $user)
    {
        $project = new Project();
        $form = $this->createForm('AppBundle\Form\ProjectType', $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $project->setCreator($user)
                ->setCreatedAt(new \DateTime())
                ->setStatus(Project::PROJECT_WORKFLOW_STEP_1);

            $openingLine = $this->chatbot->startConversation();

            $cl = (new ConversationLine())
                ->setContent($openingLine['intro'])
                ->setSpokenAt(new \DateTime())
                ->setWhom(ConversationLine::ROBOT)
                ->setProject($project);

            $project->addConversationLine($cl)
                ->setConversationId($openingLine['id']);

            $em->persist($cl);
            $em->persist($project);
            $em->flush();

            return $this->redirectToRoute('project_conversation', array('id' => $project->getId()));
        }

        return $this->render('project/add.html.twig', array(
            'project' => $project,
            'form' => $form->createView(),
        ));
    }

    /**
     * @Route("/project/{id}/reset", name="reset_conversation")
     */
    public function resetProjectConversationAction(Project $project)
    {
        $em = $this->getDoctrine()->getManager();

        foreach ($project->getConversation() as $conversationLine) {
            $em->remove($conversationLine);
        }

        foreach ($project->getTags() as $tag) {
            $em->remove($tag);
        }

        $project->setConversationId(null)
            ->setDraftLabels(null);

        $em->persist($project);
        $em->flush();

        $openingLine = $this->chatbot->startConversation();

        $cl = (new ConversationLine())
            ->setContent($openingLine['intro'])
            ->setSpokenAt(new \DateTime())
            ->setWhom(ConversationLine::ROBOT)
            ->setProject($project);

        $project
            ->setDraftLabels(null)
            ->addConversationLine($cl)
            ->setConversationId($openingLine['id'])
            ->setStatus(Project::PROJECT_WORKFLOW_STEP_1)
        ;

        $em->persist($cl);
        $em->persist($project);
        $em->flush();

        return $this->redirectToRoute('project_conversation', array('id' => $project->getId()));
    }

    /**
     * @Route("/project/{id}/conversation", name="project_conversation")
     */
    public function projectChatAction(Request $request, Project $project, UserInterface $user)
    {
        return $this->render('project/conversation.html.twig', array(
            'project' => $project,
        ));
    }

    /**
     * @Route("/project/{id}/utter", name="project_utter")
     */
    public function projectUtterAction(Request $request, Project $project, UserInterface $user)
    {
        if ($project->getStatus() > Project::PROJECT_WORKFLOW_STEP_1) {
            return new JsonResponse(['end' => true]);
        }

        $em = $this->getDoctrine()->getManager();
        $human = $request->request->get('utterance');

        $cl = (new ConversationLine())
            ->setContent($human)
            ->setSpokenAt(new \DateTime())
            ->setWhom(ConversationLine::HUMAN)
            ->setProject($project);

        $project->addConversationLine($cl);
        $em->persist($cl);

        $robotSaid = $this->chatbot->sendReply($cl);

        if (!$robotSaid) {
            $project->setStatus(Project::PROJECT_WORKFLOW_STEP_2);
        } else {
            $cl = (new ConversationLine())
                ->setContent($robotSaid->getReply())
                ->setSpokenAt(new \DateTime())
                ->setWhom(ConversationLine::ROBOT)
                ->setProject($project);

            $project->addConversationLine($cl);
            $project->addDraftLabel($robotSaid->getLabel());

            $em->persist($cl);
        }

        $em->persist($project);
        $em->flush();

        return new JsonResponse([
            'reply' => !$robotSaid ? '' : $robotSaid->getReply(),
            'end' => !$robotSaid
        ]);
    }

    /**
     * @Route("/project/{id}/validate", name="project_validate")
     */
    public function validateProjectAction(Request $request, Project $project, UserInterface $user)
    {
        if ($project->getStatus() > Project::PROJECT_WORKFLOW_STEP_2) {
            return $this->redirectToRoute('homepage');
        }

        $tags = $project->getTags();

        if ($tags->count() == 0) {
            $em = $this->getDoctrine()->getManager();
            $raw = explode('|', $project->getDraftLabels());

            $duplicates = [];
            foreach ($raw as $label) {
                /** @var Keyword $kw */
                $kw = $em->getRepository(Keyword::class)->findOneBy(['value' => $label]);
                if ($kw == null || in_array($label, $duplicates)) {
                    continue;
                }

                $duplicates[] = $label;

                $tag = (new Tag())
                    ->setProject($project)
                    ->setKeyword($kw);
                $project->addTag($tag);

                $em->persist($tag);
            }

            $em->persist($project);
            $em->flush();
        }

        return $this->render('project/validate.html.twig', array(
            'project' => $project,
        ));
    }

    /**
     * @Route("/project/{id}/publish", name="project_publish")
     */
    public function publishProjectAction(Project $project, UserInterface $user)
    {
        $project->setStatus(Project::PROJECT_WORKFLOW_STEP_3);
        $em = $this->getDoctrine()->getManager();
        $em->persist($project);
        $em->flush();

        return $this->redirectToRoute('homepage');
    }

    /**
     * @Route("/project/{id}/details", name="project_details")
     */
    public function projectDetailsAction(Request $request, Project $project, UserInterface $user)
    {
        if ($user->getType() != User::USER_TYPE_SUPPLIER) {
            return $this->redirectToRoute('project_view', ['id' => $project->getId()]);
        }

        return $this->render('project/details.html.twig', array(
            'project' => $project,
            'user' => $user,
            'userTags' => $user->getKeywords()
        ));
    }

    /**
     * @Route("/project/{id}/view", name="project_view")
     */
    public function projectViewAction(Request $request, Project $project, UserInterface $user)
    {
        if ($project->getCreator()->getId() !== $user->getId()) {
            return $this->redirectToRoute('homepage');
        }

        return $this->render('project/view.html.twig', array(
            'project' => $project,
        ));
    }

    /**
     * @Route("/project/{project}/tag/{tag}/bids/new", name="project_add_bid")
     */
    public function placeNewBidAction(Request $request, Project $project, Tag $tag, UserInterface $user, FileUploader $fileUploader)
    {
        $bid = (new Bid())
            ->setTag($tag)
            ->setSupplier($user)
            ->setCreatedAt(new \DateTime())
            ->setStatus(Bid::BID_STATUS_PENDING);
        $form = $this->createForm(BidType::class, $bid);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            /** @var UploadedFile $bidFiles */
            $bidFiles = $form->get('files')->getData();

            if ($bidFiles) {
                foreach ($bidFiles as $bidFile) {
                    $filename = $fileUploader->upload($bidFile);

                    $file = (new File())
                        ->setCreator($user)
                        ->setBid($bid)
                        ->setType(File::FILE_TYPE_OFFER)
                        ->setFilename($filename);
                    $bid->addFile($file);
                    $em->persist($file);
                }
            }

            $project->setStatus(Project::PROJECT_WORKFLOW_STEP_4);

            $em->persist($bid);
            $em->persist($project);
            $em->flush();

            return $this->redirectToRoute('project_view_bid', [
                'bid' => $bid->getId(),
                'project' => $project->getId(),
                'tag' => $tag->getId(),
            ]);
        }

        return $this->render('project/bids/new.html.twig', [
            'form' => $form->createView(),
            'tag' => $tag,
            'project' => $project,
        ]);
    }

    /**
     * @Route("/project/{project}/tag/{tag}/bids/{bid}/edit", name="project_edit_bid")
     */
    public function editBidAction(Request $request, UserInterface $user, Project $project, Tag $tag, Bid $bid)
    {
        if ($bid->getSupplier()->getId() !== $user->getId()) {
            return $this->redirectToRoute('homepage', []);
        }

        $form = $this->createForm(EditBidType::class, $bid);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $em->persist($bid);
            $em->flush();

            return $this->redirectToRoute('project_view_bid', [
                'bid' => $bid->getId(),
                'project' => $project->getId(),
                'tag' => $tag->getId(),
            ]);
        }

        return $this->render('project/bids/edit.html.twig', [
            'form' => $form->createView(),
            'bid' => $bid,
            'tag' => $tag,
            'project' => $project,
        ]);
    }

    /**
     * @Route("/project/{project}/tag/{tag}/bids/{bid}/upload", name="add_files_on_bid")
     */
    public function addFilesOnBidAction(Request $request, UserInterface $user, Project $project, Tag $tag, Bid $bid, FileUploader $fileUploader)
    {
        if ($bid->getSupplier()->getId() !== $user->getId() && $project->getCreator()->getId() !== $user->getId()) {
            return $this->redirectToRoute('homepage', []);
        }

        $form = $this->createForm(AddFilesBidType::class, $bid);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            /** @var UploadedFile $bidFiles */
            $bidFiles = $form->get('files')->getData();

            if ($bidFiles) {
                foreach ($bidFiles as $bidFile) {
                    $filename = $fileUploader->upload($bidFile);

                    $file = (new File())
                        ->setCreator($user)
                        ->setBid($bid)
                        ->setType(File::FILE_TYPE_OFFER)
                        ->setFilename($filename);
                    $bid->addFile($file);
                    $em->persist($file);
                }
            }

            $em->persist($bid);
            $em->flush();
        }

        return $this->redirectToRoute('project_view_bid', [
            'bid' => $bid->getId(),
            'tag' => $tag->getId(),
            'project' => $project->getId(),
        ]);
    }

    /**
     * @Route("/project/{project}/tag/{tag}/bids/{bid}", name="project_view_bid")
     */
    public function viewBidAction(Request $request, UserInterface $user, Project $project, Tag $tag, Bid $bid)
    {
        if ($bid->getSupplier()->getId() != $user->getId() && $project->getCreator()->getId() != $user->getId()) {
            return $this->redirectToRoute('homepage', []);
        }

        $form = $this->createForm(AddFilesBidType::class, $bid);

        return $this->render('project/bids/view.html.twig', [
            'addFiles' => $form->createView(),
            'bid' => $bid
        ]);
    }

    /**
     * @Route("/project/files/{bid}/{file}", name="project_download_file")
     */
    public function downloadFileAction(Request $request, UserInterface $user, Bid $bid, File $file)
    {
        if ($bid->getTag()->getProject()->getCreator()->getId() != $user->getId()
            && $file->getCreator()->getId() != $user->getId()
        ) {
            return $this->redirectToRoute('homepage', []);
        }

        $path = $this->getParameter('bid_files_directory');
        $toDownload = new SymfonyFile($path . '/' . $file->getFilename());

        return $this->file($toDownload, $file->getFilename());
    }

    /**
     * @Route("/project/{project}/tag/{tag}/bids/{bid}/reject", name="reject_bid")
     */
    public function rejectBidAction(Request $request, UserInterface $user, Project $project, Tag $tag, Bid $bid)
    {
        if ($user->getId() != $project->getCreator()->getId()) {
            return $this->redirectToRoute('homepage', []);
        }

        $em = $this->getDoctrine()->getManager();

        $bid->setStatus(Bid::BID_STATUS_REJECTED);

        $em->persist($bid);
        $em->flush();

        $event = new BidRejected($bid);
        $this->get('event_dispatcher')->dispatch(BidRejected::NAME, $event);

        $form = $this->createForm(AddFilesBidType::class, $bid);

        return $this->redirectToRoute('project_view_bid', [
            'bid' => $bid->getId(),
            'tag' => $tag->getId(),
            'project' => $project->getId(),
        ]);
    }

    /**
     * @Route("/project/{project}/tag/{tag}/bids/{bid}/accept", name="accept_bid")
     */
    public function acceptBidAction(Request $request, UserInterface $user, Project $project, Tag $tag, Bid $bid)
    {
        if ($user->getId() != $project->getCreator()->getId()) {
            return $this->redirectToRoute('homepage', []);
        }

        $em = $this->getDoctrine()->getManager();

        $bid->setStatus(Bid::BID_STATUS_ACCEPTED);

        $em->persist($bid);
        $em->flush();

        $event = new BidAccepted($bid);
        $this->get('event_dispatcher')->dispatch(BidAccepted::NAME, $event);

        $form = $this->createForm(AddFilesBidType::class, $bid);

        return $this->redirectToRoute('project_view_bid', [
            'bid' => $bid->getId(),
            'tag' => $tag->getId(),
            'project' => $project->getId(),
        ]);
    }

    /**
     * @Route("/project/bid/{id}/contract", name="generate_contract")
     */
    public function generateContractAction(Request $request, Bid $bid, UserInterface $user)
    {
        $filename = 'Contract.doc';
        $fileContent = $this->get('twig')->render('project/bids/contract.html.twig', ['bid' => $bid]);
        $response = new Response($fileContent);

        $disposition = $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename
        );

        $response->headers->set('Content-Disposition', $disposition);

        return $response;
    }

    /**
     * @Route("/project/{id}/close", name="close_project")
     */
    public function closeProjectAction(Request $request, Project $project, UserInterface $user)
    {
        if ($user->getId() != $project->getCreator()->getId()) {
            return $this->redirectToRoute('homepage', []);
        }

        $em = $this->getDoctrine()->getManager();

        $project->setStatus(Project::PROJECT_WORKFLOW_STEP_7);

        $em->persist($project);
        $em->flush();

        $event = new ProjectClosed($project);
        $this->get('event_dispatcher')->dispatch(ProjectClosed::NAME, $event);

        return $this->render('project/view.html.twig', array(
            'project' => $project,
        ));
    }
}
