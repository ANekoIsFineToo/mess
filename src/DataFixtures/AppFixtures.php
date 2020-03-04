<?php

namespace App\DataFixtures;

use App\Entity\Attachment;
use App\Entity\Message;
use App\Entity\Thread;
use App\Entity\User;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AppFixtures extends Fixture
{
    /** @var UserPasswordEncoderInterface $passwordEncoder */
    private $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    public function load(ObjectManager $manager)
    {
        // Usuarios
        $oliversox = $this->createUser($manager, 'oliversox', false);
        $sashamimi = $this->createUser($manager, 'sashamimi', false);
        $noodleprecious = $this->createUser($manager, 'noodleprecious', true);
        $manager->flush();

        // Amistades de los usuarios
        // El usuario sashamimi es amigo con oliversox
        $oliversox->getMyFriends()->add($sashamimi);
        $sashamimi->getMyFriends()->add($oliversox);

        // El usuario noodleprecious ha enviado una petición de amistad a sashamimi
        $noodleprecious->getMyFriends()->add($sashamimi);
        $manager->flush();

        // Conversaciones
        $oliversoxThread = $this->createThread($manager, 'Mi primera conversación', $oliversox, [$sashamimi]);
        $sashamimiThread = $this->createThread($manager, '¿Como funciona esto?', $sashamimi, [$oliversox]);
        $manager->flush();

        // Mensajes
        $this->createMessage($manager, $oliversox, $oliversoxThread, ['Un adjunto', 'Memorias']);
        $this->createMessage($manager, $sashamimi, $sashamimiThread);
        $this->createMessage($manager, $oliversox, $sashamimiThread, ['Documentación de uso']);
        $manager->flush();

    }

    private function createUser(ObjectManager $manager, string $username, bool $public): User
    {
        $user = new User();

        $user
            // El email se compone del nombre de usuario mas un sufijo constante
            ->setEmail($username . '@example.org')
            // El nombre de usuario es el indicado
            ->setUsername($username)
            // La visibilidad es la indicada
            ->setPublic($public)
            // El correo electrónico siempre está verificado
            ->setEmailVerifiedAt(new DateTime())
            // La contraseña es la misma que el nombre de usuario
            ->setPassword($this->passwordEncoder->encodePassword($user, $username));

        // Se persiste el usuario en Doctrine
        $manager->persist($user);

        return $user;
    }

    private function createThread(ObjectManager $manager, string $title, User $owner, array $members): Thread
    {
        $thread = new Thread();

        $thread
            // El título es el indicado
            ->setTitle($title)
            // El creador de la conversación es el indicado
            ->setOwner($owner)
            // Se indica una fecha de último mensaje aun sin mensajes para evitar errores de SQL
            ->setLastMessageAt(new DateTime());

        foreach ($members as $member)
        {
            // Se añaden los miembros de la conversación uno a uno
            $thread->getMembers()->add($member);
        }

        // Se persiste la conversación en Doctrine
        $manager->persist($thread);

        return $thread;
    }

    private function createMessage(ObjectManager $manager, User $owner,
                                   Thread $thread, array $attachments = []): Message
    {
        $message = new Message();

        $message
            // El creador del mensaje es el indicado
            ->setOwner($owner)
            // La conversación a la que pertenece el mensaje es el indicado
            ->setThread($thread)
            // El contenido es aleatorio obtenido desde la API de lorem ipsum
            ->setContent(file_get_contents('http://loripsum.net/api/plaintext/short'));

        foreach ($attachments as $attachment)
        {
            $attachmentEntity = (new Attachment())
                // El nombre del adjunto es el indicado
                ->setFilename($attachment)
                // La ruta el aleatoria por lo que no se podrá descargar
                ->setPath(Uuid::uuid4())
                // El mensaje al que pertenece el adjunto es el que está siendo creado
                ->setMessage($message);

            // Se persiste el adjunto en Doctrine
            $manager->persist($attachmentEntity);
        }

        // Se persiste el mensaje en Doctrine
        $manager->persist($message);

        // La fecha del último mensaje de la conversación es actualizada
        $thread->setLastMessageAt(new DateTime());

        return $message;
    }
}
