<?php

namespace App\Controller;

use App\Form\EditProfileFormType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * Class ProfileController
 * @package App\Controller
 *
 * @Route("/profile")
 */
class ProfileController extends AbstractController
{
    /**
     * @Route("/", name="user_profile_index")
     */
    public function index(): Response
    {
        $editProfileForm = $this->createForm(EditProfileFormType::class, $this->getUser());
        return $this->render('profile/index.html.twig', ['editProfileForm' => $editProfileForm->createView()]);
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

            $em->persist($user);
            $em->flush();

            return new Response('', Response::HTTP_NO_CONTENT);
        }

        $em->refresh($user);

        return new Response(
            $this->renderView('fragments/user-edit-form.html.twig', ['form' => $form->createView()]),
            Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    private function getErrorsFromForm(FormInterface $form): array
    {
        $errors = [];

        foreach ($form->getErrors() as $error)
        {
            $errors[] = $error->getMessage();
        }

        foreach ($form->all() as $childForm)
        {
            if (($childForm instanceof FormInterface) && $childErrors = $this->getErrorsFromForm($childForm))
            {
                $errors[$childForm->getName()] = $childErrors;
            }
        }

        return $errors;
    }
}
