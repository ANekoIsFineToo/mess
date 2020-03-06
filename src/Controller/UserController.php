<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class UserController
 * @package App\Controller
 *
 * @Route("/user")
 */
class UserController extends AbstractController
{
    /**
     * @Route("/{uuid}", name="user_user_profile", methods={"GET"})
     */
    public function profile(string $uuid): Response
    {
        /** @var User $currentUser */
        $currentUser = $this->getUser();

        if ($currentUser->getUuid()->toString() === $uuid)
        {
            // Si el usuario actual intenta visitar su perfil se le redirige a la página de perfil
            return $this->redirectToRoute('user_profile_index');
        }

        // El usuario que está siendo visitado es buscado en la base de datos a partir del UUID
        $userRepository = $this->getDoctrine()->getRepository(User::class);
        $targetUser = $userRepository->findOneBy(['uuid' => $uuid]);

        if ($targetUser === null)
        {
            // Si el usuario no existe se envía un error 404
            throw new NotFoundHttpException('User not found.');
        }

        // Se buscan los amigos del usuario al que se está visitando
        $friends = $userRepository->getUserFriends($targetUser);

        // Se comprueba si el usuario actual es un amigo confirmado del usuario que se está visitando
        $isFriend = $userRepository->isFriendOf($currentUser, $targetUser);

        // Se comprueba si el usuario actual ya ha mandado una petición de amistad al usuario que está siendo visitado
        $sentFriendRequest = $currentUser->getMyFriends()->contains($targetUser);

        return $this->render('user/profile.html.twig', [
            'user' => $targetUser,
            'friends' => $friends,
            'isFriend' => $isFriend,
            'sentFriendRequest' => $sentFriendRequest
        ]);
    }
}
