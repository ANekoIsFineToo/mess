<?php

namespace App\DataFixtures;

use App\Entity\User;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
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
        $usernames = [
            'oliversox',
            'sashamimi',
            'noodleprecious',
            'tomcatminka',
            'scaredymilo'
        ];

        foreach($usernames as $index => $username)
        {
            $user = new User();
            $user->setEmail($username . '@example.org');
            $user->setUsername($username);
            $user->setPublic($index % 2 === 0);
            $user->setEmailVerifiedAt(new DateTime());
            $user->setPassword($this->passwordEncoder->encodePassword($user, $username));
            $manager->persist($user);
        }

        $manager->flush();
    }
}
