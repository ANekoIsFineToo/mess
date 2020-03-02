<?php

namespace App\Entity;

use App\Util\Doctrine\TimeableInterface;
use App\Util\Doctrine\TimeableTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\MessageRepository")
 */
class Message implements TimeableInterface
{
    use TimeableTrait;

    /**
     * @var integer|null Identificador interno de la entidad
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string|null Contenido del mensaje
     * @ORM\Column(type="text")
     */
    private $content;

    /**
     * @var Collection Adjuntos enviados en el mensaje
     * @ORM\OneToMany(targetEntity="Attachment", mappedBy="message")
     */
    private $attachments;

    /**
     * @var Thread|null Conversación a la que pertenece el mensaje
     * @ORM\ManyToOne(targetEntity="Thread", inversedBy="messages")
     * @ORM\JoinColumn(name="thread_id", referencedColumnName="id")
     */
    private $thread;

    /**
     * @var User|null Usuario que ha enviado el mensaje
     * @ORM\ManyToOne(targetEntity="User", inversedBy="messages")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $owner;

    public function __construct()
    {
        $this->attachments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getAttachments(): Collection
    {
        return $this->attachments;
    }

    public function getThread(): ?Thread
    {
        return $this->thread;
    }

    public function setThread(?Thread $thread): self
    {
        $this->thread = $thread;
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
}
