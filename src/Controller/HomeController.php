<?php

namespace App\Controller;

use App\Entity\Attachment;
use App\Entity\Message;
use App\Entity\Thread;
use App\Entity\User;
use App\Form\NewMessageFormType;
use App\Form\NewThreadFormType;
use DateTime;
use Doctrine\Persistence\ObjectManager;
use Exception;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    /** @var LoggerInterface $logger */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @Route(path="/", name="user_home_index")
     */
    public function index(Request $request): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        $thread = new Thread();
        $threadForm = $this->createForm(NewThreadFormType::class, $thread, ['user' => $currentUser]);
        $threadForm->handleRequest($request);

        if ($threadForm->isSubmitted() && $threadForm->isValid())
        {
            $thread->setLastMessageAt(new DateTime());
            $thread->setOwner($currentUser);

            /** @var User[]|null[] $members */
            $members = $threadForm->get('members')->getData();

            $entityManager = $this->getDoctrine()->getManager();

            foreach ($members as $member)
            {
                if ($member !== null && $member->getMyFriends()->contains($currentUser))
                {
                    $thread->getMembers()->add($member);
                }
            }

            /** @var Message $message */
            $message = $threadForm->get('message')->getData();

            $message->setThread($thread);
            $message->setOwner($currentUser);

            /** @var UploadedFile[] $attachments */
            $attachments = $threadForm->get('message')->get('attachments')->getData();

            $this->handleAttachments($message, $attachments, $entityManager);

            $entityManager->persist($message);
            $entityManager->persist($thread);
            $entityManager->flush();

            return $this->redirectToRoute('user_home_index');
        }

        $threadRepository = $this->getDoctrine()->getRepository(Thread::class);
        $joinedThreads = $threadRepository->getJoinedThreads($currentUser);

        return $this->render('home/index.html.twig', [
            'threadForm' => $threadForm->createView(),
            'threadFormHasErrors' => $threadForm->isSubmitted() && !$threadForm->isValid(),
            'joinedThreads' => $joinedThreads
        ]);
    }

    /**
     * @Route(path="/thread/{uuid}", name="user_home_read")
     */
    public function read(string $uuid, Request $request): Response
    {
        $threadRepository = $this->getDoctrine()->getRepository(Thread::class);
        $thread = $threadRepository->findOneBy(['uuid' => $uuid]);

        if ($thread === null)
        {
            throw new NotFoundHttpException('Thread not found.');
        }

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        if ($thread->getOwner()->getId() !== $currentUser->getId()
            && !$threadRepository->isMemberOf($currentUser, $thread))
        {
            throw new UnauthorizedHttpException('Thread not joined.');
        }

        $message = new Message();
        $messageForm = $this->createForm(NewMessageFormType::class, $message);
        $messageForm->handleRequest($request);

        if ($messageForm->isSubmitted() && $messageForm->isValid())
        {
            /** @var UploadedFile[] $attachments */
            $attachments = $messageForm->get('attachments')->getData();

            $entityManager = $this->getDoctrine()->getManager();

            $this->handleAttachments($message, $attachments, $entityManager);

            $message->setThread($thread);
            $message->setOwner($currentUser);

            $thread->setLastMessageAt(new DateTime());

            $entityManager->persist($message);
            $entityManager->flush();
        }

        $members = $this->getDoctrine()->getRepository(User::class)->getMembersOfThread($thread);

        return $this->render('home/read.html.twig', [
            'thread' => $thread,
            'members' => $members,
            'messageForm' => $messageForm->createView()
        ]);
    }

    private function handleAttachments(Message $message, array $attachments, ObjectManager $entityManager): void
    {
        foreach ($attachments as $attachment)
        {
            try
            {
                $safeAttachmentName = Uuid::uuid4();
                $attachment->move($this->getParameter('attachments_directory'), $safeAttachmentName);

                $attachmentEntity = new Attachment();
                $attachmentEntity->setFilename($attachment->getClientOriginalName());
                $attachmentEntity->setPath($safeAttachmentName);

                $message->getAttachments()->add($attachmentEntity);
                $entityManager->persist($attachmentEntity);
            }
            catch (FileException $e)
            {
                $this->logger->error(
                    'Error while saving attachment into file system',
                    ['message' => $e->getMessage()]
                );
            }
            catch (Exception $e)
            {
                $this->logger->critical(
                    'Error while generating an UUID for an attachment',
                    ['message' => $e->getMessage()]
                );
            }
        }
    }
}
