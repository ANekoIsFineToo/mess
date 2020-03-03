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

/**
 * @ORM\Entity(repositoryClass="App\Repository\ThreadRepository")
 */
class Thread implements UuidableInterface, TimeableInterface
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
     * @var string|null Título de la conversación
     * @ORM\Column(type="string")
     */
    private $title;

    /**
     * @var DateTime|null Fecha en la que se envió el último mensaje de la conversación
     * @ORM\Column(name="last_message_at", type="datetime")
     */
    private $lastMessageAt;

    /**
     * @var User|null Usuario que inicio la conversación
     * @ORM\ManyToOne(targetEntity="User", inversedBy="ownedThreads")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $owner;

    /**
     * @var Collection Miembros añadidos a la conversación de forma individual
     * @ORM\ManyToMany(targetEntity="User", inversedBy="joinedThreads")
     * @ORM\JoinTable(name="threads_users")
     */
    private $members;

    /**
     * @var Collection Mensajes enviados por el usuario
     * @ORM\OneToMany(targetEntity="Message", mappedBy="thread")
     * @ORM\OrderBy({"createdAt" = "DESC"})
     */
    private $messages;

    /**
     * @var Collection Lecturas de la conversación por los miembros
     * @ORM\OneToMany(targetEntity="ThreadRead", mappedBy="thread")
     */
    private $reads;

    /** @var bool Indica si la conversación está leída por el usuario */
    private $read;

    public function __construct()
    {
        $this->members = new ArrayCollection();
        $this->messages = new ArrayCollection();
        $this->reads = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getLastMessageAt(): ?DateTime
    {
        return $this->lastMessageAt;
    }

    public function setLastMessageAt(?DateTime $lastMessageAt): self
    {
        $this->lastMessageAt = $lastMessageAt;
        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): self
    {
        $this->owner = $owner;
        return $this;
    }

    public function getMembers(): Collection
    {
        return $this->members;
    }

    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function getReads(): Collection
    {
        return $this->reads;
    }

    public function isRead(): bool
    {
        return $this->read;
    }

    public function setRead(bool $read): self
    {
        $this->read = $read;
        return $this;
    }
}
