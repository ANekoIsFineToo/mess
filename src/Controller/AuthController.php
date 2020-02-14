<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Exception;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class AuthController extends AbstractController
{
    /** @var MailerInterface $mailer */
    private $mailer;

    /** @var LoggerInterface $logger */
    private $logger;

    /** @var UserPasswordEncoderInterface $passwordEncoder */
    private $passwordEncoder;

    public function __construct(MailerInterface $mailer, LoggerInterface $logger,
                                UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->mailer = $mailer;
        $this->logger = $logger;
        $this->passwordEncoder = $passwordEncoder;
    }

    /**
     * Ruta para registrar una nueva cuenta.
     *
     * @Route("/register", name="auth_register")
     */
    public function register(Request $request): Response
    {
        if ($this->getUser())
        {
            // Redirigir la usuario a la página de inicio si ya ha iniciado sesión
            return $this->redirectToRoute('home_index');
        }

        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid())
        {
            // Se codifica y asigna la contraseña enviada al usuario
            $user->setPassword(
                $this->passwordEncoder->encodePassword(
                    $user,
                    $form->get('password')->getData()
                )
            );

            /** @var UploadedFile $avatarFile */
            $avatarFile = $form->get('avatar')->getData();

            if ($avatarFile)
            {
                // Si en el momento del registro el usuario ha enviado un avatar se almacena y registra en su cuenta
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

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            // El correo para que el usuario pueda verificar su cuenta es enviado
            $this->sendVerificationEmail($user->getEmail(), $user->getUsername());

            // Una vez el usuario se ha registrado completamente se le lleva a la página de inicio de sesión con un mensaje
            $this->addFlash(
                'success',
                'Tu cuenta ha sido creada correctamente, puedes confirmarla desde tu correo electrónico.'
            );
            return $this->redirectToRoute('auth_login');
        }

        return $this->render('auth/register.html.twig', ['registrationForm' => $form->createView()]);
    }

    /**
     * Ruta para iniciar sesión.
     *
     * @Route("/login", name="auth_login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser())
        {
            // Redirigir la usuario a la página de inicio si ya ha iniciado sesión
            return $this->redirectToRoute('home_index');
        }

        // Se obtiene el mensaje de error si se ha producido alguno
        $error = $authenticationUtils->getLastAuthenticationError();
        // El último email introducido por el usuario
        $lastEmail = $authenticationUtils->getLastUsername();

        return $this->render('auth/login.html.twig', [
            'last_email' => $lastEmail,
            'error' => $error
        ]);
    }

    /**
     * Ruta para verificar un correo electrónico.
     *
     * @Route("/verify/{encryptedEmail}", name="auth_verify_email")
     *
     * @throws EnvironmentIsBrokenException
     */
    public function verifyEmail(string $encryptedEmail): Response
    {
        if ($this->getUser())
        {
            // Redirigir la usuario a la página de inicio si ya ha iniciado sesión
            return $this->redirectToRoute('home_index');
        }

        $entityManager = $this->getDoctrine()->getManager();

        try
        {
            // El email recibido es desencriptado con el secreto de la aplicación
            $email = Crypto::decryptWithPassword($encryptedEmail, $this->getParameter('app_secret'));

            // Se busca al usuario utilizando el email, comprobando que no ha verificado ya su cuenta
            $user = $entityManager
                ->getRepository(User::class)
                ->findOneBy(['email' => $email, 'emailVerifiedAt' => null]);

            if ($user === null)
            {
                // Si su cuenta ya ha sido verificada se le notifica
                // El email siempre será válido en este punto ya que si no lo fuese no puede ser desencriptado
                $this->addFlash(
                    'info',
                    'El correo electrónico ya ha sido verificado, puedes iniciar sesión.'
                );
                return $this->redirectToRoute('auth_login');
            }

            // Se define la fecha de verificación como este mismo momento
            $user->setEmailVerifiedAt(new \DateTime());
            $entityManager->flush();

            // Una vez verificada la cuenta se reenvía al usuario a la página de inicio de sesión
            $this->addFlash(
                'success',
                'Has verificado el correo electrónico satisfactoriamente, ya puedes iniciar sesión en tu cuenta.'
            );
            return $this->redirectToRoute('auth_login');
        }
        catch (WrongKeyOrModifiedCiphertextException $e)
        {
            // Si el email no puede ser desencriptado se muestra un mensaje de error al usuario
            return $this->render('auth/invalid-email-confirmation.html.twig');
        }
        catch (EnvironmentIsBrokenException $e)
        {
            throw $e;
        }
    }

    /**
     * Ruta para cerrar sesión.
     *
     * @Route("/logout", name="auth_logout")
     */
    public function logout(): void
    {
        // Este método puede estar vacío, es interceptado por el firewall
    }

    /**
     * Ruta para reiniciar la contraseña de un usuario.
     *
     * @Route("/reset-password", name="auth_reset_password")
     */
    public function resetPassword(Request $request): Response
    {
        if ($request->isMethod('POST'))
        {
            $form = $request->request->get('reset_password_form');

            if (!$this->isCsrfTokenValid('reset-password', $form['_token']))
            {
                throw new InvalidCsrfTokenException();
            }

            $email = $form['email'];
            $userRepository = $this->getDoctrine()->getManager()->getRepository(User::class);
            $user = $userRepository->findOneBy(['email' => $email]);

            if (!$user)
            {
                // Enviar un error si el usuario no ha sido encontrado
                throw new CustomUserMessageAuthenticationException(
                    'No se ha encontrado ningún usuario con el correo electrónico introducido.'
                );
            }

            $newPassword = sha1(random_bytes(8));
            $userRepository->upgradePassword($user, $this->passwordEncoder->encodePassword($user, $newPassword));

            try
            {
                // Se compone el mensaje que se enviará
                $confirmationEmail = (new TemplatedEmail())
                    ->to($user->getEmail())
                    ->priority(Email::PRIORITY_HIGH)
                    ->subject('Tu contraseña ha sido actualizada con éxito')
                    ->htmlTemplate('emails/reset-password-complete.html.twig')
                    ->context(['newPassword' => $newPassword]);

                // Finalmente se envía el mensaje
                $this->mailer->send($confirmationEmail);
            }
            catch (TransportExceptionInterface $e)
            {
                // Error mientras se intentaba enviar el mensaje
                $this->logger->error('Error while sending verification email', ['message' => $e->getMessage()]);
            }

            $this->addFlash(
                'success',
                'Tu contraseña ha sido reiniciada por una aleatoria, comprueba tu correo electrónico para utilizarla.'
            );
            return $this->redirectToRoute('auth_login');
        }

        return $this->render('auth/reset-password.html.twig');
    }

    /**
     * Envía un correo al usuario para que pueda verificar su cuenta.
     *
     * @param string $email     correo electrónico al que se enviará el mensaje
     * @param string $username  nombre de usuario del usuario al que se enviará el correo
     */
    private function sendVerificationEmail(string $email, string $username): void
    {
        try
        {
            // El email del usuario es encriptado utilizando el secreto de la aplicación
            $encryptedEmail =
                Crypto::encryptWithPassword($email, $this->getParameter('app_secret'));

            // Se compone el mensaje que se enviará
            $confirmationEmail = (new TemplatedEmail())
                ->to($email)
                ->priority(Email::PRIORITY_HIGH)
                ->subject('¡Confirma tu cuenta en Mess!')
                ->htmlTemplate('emails/registration.html.twig')
                ->context([
                    'username' => $username,
                    'encryptedEmail' => $encryptedEmail
                ]);

            // Finalmente se envía el mensaje
            $this->mailer->send($confirmationEmail);
        }
        catch (TransportExceptionInterface $e)
        {
            // Error mientras se intentaba enviar el mensaje
            $this->logger->error('Error while sending verification email', ['message' => $e->getMessage()]);
        }
        catch (EnvironmentIsBrokenException $e)
        {
            // Error mientras se intentaba encriptar el email
            $this->logger->error('Error while encrypting user email', ['message' => $e->getMessage()]);
        }
    }

}
