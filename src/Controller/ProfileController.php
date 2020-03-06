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

        // Se comprueba si el formulario de actualización del perfil ha sido enviado y es válido
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

            // Los cambios en el perfil del usuario son persistidos en Doctrine
            $em->persist($user);
            $em->flush();

            return new Response('', Response::HTTP_NO_CONTENT);
        }

        // Para evitar valores erráticos en el formulario el objeto del usuario es refrescado
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
            // Si el nombre de usuario enviado está vacío o tiene menos de tres caracteres se envía un error 422
            throw new UnprocessableEntityHttpException('Invalid username specified');
        }

        /** @var User $user */
        $user = $this->getUser();

        $em = $this->getDoctrine()->getManager();

        // Se busca en la base de datos 5 usuarios que coincidan con el nombre de usuario enviado
        $foundUsers = $em->getRepository(User::class)->searchFriends($user, $username, 5);

        if (empty($foundUsers))
        {
            // Si no se ha encontrado ningún usuario con el nombre de usuario enviado se envía un error 404
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
            // Si el token CSRF no es válido es posible que la petición no sea legitima
            throw new InvalidCsrfTokenException();
        }

        $em = $this->getDoctrine()->getManager();

        // Se busca el usuario que se va a añadir a partir de su UUID
        $targetUser = $em->getRepository(User::class)->findOneBy(['uuid' => $uuid]);

        if ($targetUser === null)
        {
            // Si no se ha encontrado el usuario que se quiere añadir se envía un erro 404
            throw new NotFoundHttpException('User not found');
        }

        /** @var User $user */
        $user = $this->getUser();

        // El usuario buscado es añadido a la lista de amigos del usuario actual
        $user->getMyFriends()->add($targetUser);
        $em->flush();

        if ($targetUser->getMyFriends()->contains($user))
        {
            // El usuario actual ha aceptado una petición de amistad
            $this->addFlash('success', 'Solicitud de amistad aceptada con éxito.');
        }
        else
        {
            // El usuario actual ha enviado una petición de amistad
            $this->addFlash('success', 'Solicitud de amistad enviada con éxito.');
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
            // Si el token CSRF no es válido es posible que la petición no sea legitima
            throw new InvalidCsrfTokenException();
        }

        $em = $this->getDoctrine()->getManager();

        // Se busca el usuario que se va a añadir a partir de su UUID
        $targetUser = $em->getRepository(User::class)->findOneBy(['uuid' => $uuid]);

        if ($targetUser === null)
        {
            // Si no se ha encontrado el usuario que se quiere eliminar se envía un erro 404
            throw new NotFoundHttpException('User not found');
        }

        /** @var User $user */
        $user = $this->getUser();

        $friendConfirmed = $targetUser->getMyFriends()->contains($user);

        if ($friendConfirmed)
        {
            // Si el usuario objetivo también ha añadido al usuario actual se elimina esa amistad
            $targetUser->getMyFriends()->removeElement($user);
        }

        // Se elimina la amistad del usuario actual con el usuario objetivo
        $user->getMyFriends()->removeElement($targetUser);

        // Los cambios son persistidos
        $em->flush();

        if ($friendConfirmed)
        {
            // El usuario ha eliminado la amistad de un usuario
            $this->addFlash('success', 'Amistad eliminada con éxito.');
        }
        else
        {
            // El usuario ha cancelado una petición de amistad a un usuario
            $this->addFlash('success', 'Solicitud de amistad cancelada con éxito.');
        }

        return $this->redirectToRoute('user_profile_index');
    }
}
