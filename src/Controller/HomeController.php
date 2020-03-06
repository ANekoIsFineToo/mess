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

        // Se comprueba si el formulario de creación de una conversación ha sido enviado y es válido
        if ($threadForm->isSubmitted() && $threadForm->isValid())
        {
            // Se define la fecha del último mensaje como la actual, y el creador como el usuario actual
            $thread->setLastMessageAt(new DateTime());
            $thread->setOwner($currentUser);

            /** @var User[]|null[] $members */
            $members = $threadForm->get('members')->getData();

            foreach ($members as $member)
            {
                // Los miembros enviados son añadidos a la lista de miembros de la conversación,
                // únicamente si los miembros enviados pertenecen a la lista de amigos del usuario actual
                if ($member !== null && $member->getMyFriends()->contains($currentUser))
                {
                    $thread->getMembers()->add($member);
                }
            }

            /** @var Message $message */
            $message = $threadForm->get('message')->getData();

            // Se indica la conversación y el creador del primer mensaje como los actuales
            $message->setThread($thread);
            $message->setOwner($currentUser);

            $entityManager = $this->getDoctrine()->getManager();

            /** @var UploadedFile[] $attachments */
            $attachments = $threadForm->get('message')->get('attachments')->getData();

            // Los adjuntos enviados al crear la conversación son controlados y añadidos al primer mensaje
            $this->handleAttachments($message, $attachments, $entityManager);

            // La conversación y el primer mensaje son persistidos
            $entityManager->persist($message);
            $entityManager->persist($thread);
            $entityManager->flush();

            return $this->redirectToRoute('user_home_read', ['uuid' => $thread->getUuid()]);
        }

        $threadRepository = $this->getDoctrine()->getRepository(Thread::class);

        // Se obtiene la lista de conversaciones a las que pertenece el usuario
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

        // Se busca la conversación a la que se intenta acceder por el UUID
        $thread = $threadRepository->findOneBy(['uuid' => $uuid]);

        if ($thread === null)
        {
            // Si la conversación no existe se envía un error 404
            throw new NotFoundHttpException('Thread not found.');
        }

        /** @var User $currentUser */
        $currentUser = $this->getUser();

        // Se comprueba que el usuario es el creador, o miembro, de la conversación
        if ($thread->getOwner()->getId() !== $currentUser->getId()
            && !$threadRepository->isMemberOf($currentUser, $thread))
        {
            // Si no es creador o miembros de la conversación se envía un error 401
            throw new UnauthorizedHttpException('Thread not joined.');
        }

        $message = new Message();
        $messageForm = $this->createForm(NewMessageFormType::class, $message);
        $messageForm->handleRequest($request);

        // Se comprueba si el formulario de creación de una respuesta ha sido enviado y es válido
        if ($messageForm->isSubmitted() && $messageForm->isValid())
        {
            $entityManager = $this->getDoctrine()->getManager();

            /** @var UploadedFile[] $attachments */
            $attachments = $messageForm->get('attachments')->getData();

            // Los adjuntos enviados para ser incluidos en el mensaje son procesados
            $this->handleAttachments($message, $attachments, $entityManager);

            // Se define la conversación y el creador del mensaje, ambos son los actuales
            $message->setThread($thread);
            $message->setOwner($currentUser);

            // La fecha del último mensaje de la conversación es actualizada
            $thread->setLastMessageAt(new DateTime());

            // El mensaje y los cambios de la conversación son persistidos
            $entityManager->persist($message);
            $entityManager->flush();

            // Si no se vacía la cache de Doctrine no se ven los adjuntos del nuevo mensaje
            $entityManager->clear();
        }

        // La fecha de la última lectura del usuario en la conversación es actualizada
        $threadRepository->updateRead($currentUser, $thread);

        // Se buscan todos los miembros de la conversación
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
                // Se asigna un nombre aleatorio al adjunto
                $safeAttachmentName = Uuid::uuid4();

                // Utilizando el nombre aleatorio el adjunto es movido de la carpeta temporal a la definitiva
                $attachment->move($this->getParameter('attachments_directory'), $safeAttachmentName);

                // Una nueva entidad para el adjunto es creada
                $attachmentEntity = new Attachment();
                // Se define el nombre original para la futura descarga
                $attachmentEntity->setFilename($attachment->getClientOriginalName());
                // Se define la ruta en la que se ha almacenado el adjunto
                $attachmentEntity->setPath($safeAttachmentName);
                // Se define el mensaje al que pertenece el adjunto
                $attachmentEntity->setMessage($message);

                // Finalmente la nueva entidad del adjunto es persistida
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
