<?php

namespace App\Entity;

use App\Util\Doctrine\TimeableInterface;
use App\Util\Doctrine\TimeableTrait;
use App\Util\Doctrine\UuidableInterface;
use App\Util\Doctrine\UuidableTrait;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\PersistentCollection;
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
     * @var DateTime|null Fecha en la que el usuario verificó su correo electrónico
     * @ORM\Column(name="email_verified_at", type="datetime", nullable=true)
     */
    private $emailVerifiedAt;

    /**
     * @var string|null Contraseña con un hash aplicado
     * @ORM\Column(type="string")
     */
    private $password;

    /**
     * @var Collection Usuarios que son amigo con el usuario
     * @ManyToMany(targetEntity="User", mappedBy="myFriends")
     */
    private $friendsWithMe;

    /**
     * @var Collection Usuarios que son amigos del usuario
     * @ManyToMany(targetEntity="User", inversedBy="friendsWithMe")
     * @JoinTable(name="friends",
     *     joinColumns={@JoinColumn(name="user_id", referencedColumnName="id")},
     *     inverseJoinColumns={@JoinColumn(name="friend_user_id", referencedColumnName="id")}
     *     )
     */
    private $myFriends;

    public function __construct()
    {
        $this->friendsWithMe = new ArrayCollection();
        $this->myFriends = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getAvatar(): ?UuidInterface
    {
        return $this->avatar;
    }

    public function setAvatar(?UuidInterface $avatar): self
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function getPublic(): ?bool
    {
        return $this->public;
    }

    public function setPublic(?bool $public): self
    {
        $this->public = $public;

        return $this;
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

    public function getEmailVerifiedAt(): ?DateTime
    {
        return $this->emailVerifiedAt;
    }

    public function setEmailVerifiedAt(?DateTime $emailVerifiedAt): self
    {
        $this->emailVerifiedAt = $emailVerifiedAt;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getFriendsWithMe(): Collection
    {
        return $this->friendsWithMe;
    }

    public function getMyFriends(): Collection
    {
        return $this->myFriends;
    }

    /**
     * @see UserInterface
     */
    public function getSalt(): void
    {
        // not needed when using the "bcrypt" algorithm in security.yaml
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }
}
