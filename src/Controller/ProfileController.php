<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\EditProfileFormType;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Class ProfileController
 * @package App\Controller
 *
 * @Route("/profile")
 */
class ProfileController extends AbstractController
{
    /** @var CsrfTokenManagerInterface $csrfTokenManager */
    private $csrfTokenManager;

    public function __construct(CsrfTokenManagerInterface $csrfTokenManager)
    {
        $this->csrfTokenManager = $csrfTokenManager;
    }

    /**
     * @Route("/", name="user_profile_index", methods={"GET"})
     */
    public function index(): Response
    {
        // Formulario de edición del perfil
        $editProfileForm = $this->createForm(EditProfileFormType::class, $this->getUser());

        /** @var User $user */
        $user = $this->getUser();
        $userRepository = $this->getDoctrine()->getRepository(User::class);

        // Lista de amigos confirmados del usuario
        $userFriends = $userRepository->getUserFriends($user);

        // Lista de usuarios que el usuario ha enviado una petición de amistad
        $userPendingSent = $userRepository->getUserPendingSent($user);

        // Lista de usuarios que han enviado una petición de amistad al usuario
        $userPendingReceived = $userRepository->getUserPendingReceived($user);

        return $this->render('profile/index.html.twig', [
            'editProfileForm' => $editProfileForm->createView(),
            'userFriends' => $userFriends,
            'userPendingSent' => $userPendingSent,
            'userPendingReceived' => $userPendingReceived
        ]);
    }

    /**
     * @Route("/edit", name="user_profile_edit", methods={"POST"})
     */
    public function ajaxEditProfile(Request $request, UserPasswordEncoderInterface $passwordEncoder): Response
    {
        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        $form = $this->createForm(EditProfileFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            // Si el usuario ha seleccionado cambiar la contraseña, esta es recogida y asignada
            $password = $form->get('password')->getData();

            if (!empty($password))
            {
                // Se codifica y asigna la contraseña enviada al usuario
                $user->setPassword($passwordEncoder->encodePassword($user, $password));
            }

            /** @var UploadedFile $avatarFile */
            $avatarFile = $form->get('avatar')->getData();

            if ($avatarFile)
            {
                // Si el usuario ha enviado un nuevo avatar se almacena y registra en su cuenta
                try
                {
                    $safeAvatarName = Uuid::uuid4();

                    $avatarFile->move($this->getParameter('avatars_directory'), $safeAvatarName);
                    $user->setAvatar($safeAvatarName);
                }
                catch (FileException $e)
                {
                    $this->logger->error(
                        'Error while saving avatar into file system',
                        ['message' => $e->getMessage()]
                    );
                }
                catch (Exception $e)
                {
                    $this->logger->critical(
                        'Error while generating an UUID for an avatar',
                        ['message' => $e->getMessage()]
                    );
                }
            }

            $em->persist($user);
            $em->flush();

            return new Response('', Response::HTTP_NO_CONTENT);
        }

        $em->refresh($user);

        return new Response(
            $this->renderView('fragments/user-edit-form.html.twig', ['form' => $form->createView()]),
            Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @Route("/friend/search", name="user_profile_friend_search", methods={"POST"})
     */
    public function ajaxFriendSearch(Request $request): Response
    {
        $username = $request->request->get('username');

        if (empty($username) || strlen($username) < 3)
        {
            throw new UnprocessableEntityHttpException('Invalid username specified');
        }

        /** @var User $user */
        $user = $this->getUser();

        $em = $this->getDoctrine()->getManager();
        $foundUsers = $em->getRepository(User::class)->searchFriends($user, $username, 5);

        if (empty($foundUsers)) {
            throw new NotFoundHttpException('Username not found');
        }

        return $this->render('fragments/user-add-friend-card.html.twig', ['foundUsers' => $foundUsers]);
    }

    /**
     * @Route("/friend/add", name="user_profile_friend_add", methods={"POST"})
     */
    public function friendAdd(Request $request): Response
    {
        $uuid = $request->request->get('uuid');
        $token = new CsrfToken('add-friend', $request->request->get('_token'));

        if (!$this->csrfTokenManager->isTokenValid($token))
        {
            throw new InvalidCsrfTokenException();
        }

        $em = $this->getDoctrine()->getManager();
        $targetUser = $em->getRepository(User::class)->findOneBy(['uuid' => $uuid]);

        if ($targetUser !== null) {
            /** @var User $user */
            $user = $this->getUser();

            $user->getMyFriends()->add($targetUser);
            $em->flush();

            if ($targetUser->getMyFriends()->contains($user))
            {
                $this->addFlash('success', 'Solicitud de amistad aceptada con éxito.');
            }
            else
            {
                $this->addFlash('success', 'Solicitud de amistad enviada con éxito.');
            }
        }
        else
        {
            throw new NotFoundHttpException('User not found');
        }

        return $this->redirectToRoute('user_profile_index');
    }

    /**
     * @Route("/friend/remove", name="user_profile_friend_remove", methods={"POST"})
     */
    public function friendRemove(Request $request): Response
    {
        $uuid = $request->request->get('uuid');
        $token = new CsrfToken('remove-friend', $request->request->get('_token'));

        if (!$this->csrfTokenManager->isTokenValid($token))
        {
            throw new InvalidCsrfTokenException();
        }

        $em = $this->getDoctrine()->getManager();
        $targetUser = $em->getRepository(User::class)->findOneBy(['uuid' => $uuid]);

        if ($targetUser !== null)
        {
            /** @var User $user */
            $user = $this->getUser();

            $friendConfirmed = $targetUser->getMyFriends()->contains($user);

            $user->getMyFriends()->removeElement($targetUser);

            if ($friendConfirmed)
            {
                $targetUser->getMyFriends()->removeElement($user);
            }

            $em->flush();

            if ($friendConfirmed)
            {
                $this->addFlash('success', 'Amistad eliminada con éxito.');
            }
            else
            {
                $this->addFlash('success', 'Solicitud de amistad cancelada con éxito.');
            }
        }
        else
        {
            throw new NotFoundHttpException('User not found');
        }

        return $this->redirectToRoute('user_profile_index');
    }
}
