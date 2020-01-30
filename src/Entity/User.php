<?php

namespace App\Entity;

use App\Util\Doctrine\TimeableInterface;
use App\Util\Doctrine\TimeableTrait;
use App\Util\Doctrine\UuidableInterface;
use App\Util\Doctrine\UuidableTrait;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @UniqueEntity(fields={"email"}, message="Ya existe un usuario con este correo electrónico")
 */
class User implements UserInterface, UuidableInterface, TimeableInterface
{
    use UuidableTrait;
    use TimeableTrait;

    /**
     * @var integer|null Identificador interno de la entidad
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string|null Correo electrónico asociado
     * @ORM\Column(type="string", length=180, unique=true)
     */
    private $email;

    /**
     * @var string|null Nombre del usuario dentro de la aplicación
     * @ORM\Column(type="string", length=18)
     */
    private $username;

    /**
     * @var UuidInterface|null Nombre del avatar en forma de identificador único, en caos de que el usuario tenga uno
     * @ORM\Column(type="uuid", nullable=true, unique=true)
     */
    private $avatar;

    /**
     * @var bool|null Indica si el usuario tiene un perfil público
     * @ORM\Column(type="boolean")
     */
    private $public;

    /**
     * @var string[] Roles del usuario en la aplicación
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @var string|null Contraseña con un hash aplicado
     * @ORM\Column(type="string")
     */
    private $password;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @return UuidInterface|null
     */
    public function getAvatar(): ?UuidInterface
    {
        return $this->avatar;
    }

    /**
     * @param UuidInterface|null $avatar
     */
    public function setAvatar(?UuidInterface $avatar): void
    {
        $this->avatar = $avatar;
    }

    /**
     * @return bool|null
     */
    public function getPublic(): ?bool
    {
        return $this->public;
    }

    /**
     * @param bool|null $public
     */
    public function setPublic(?bool $public): void
    {
        $this->public = $public;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function getSalt()
    {
        // not needed when using the "bcrypt" algorithm in security.yaml
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }
}
