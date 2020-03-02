<?php

namespace App\Entity;

use App\Util\Doctrine\TimeableInterface;
use App\Util\Doctrine\TimeableTrait;
use App\Util\Doctrine\UuidableInterface;
use App\Util\Doctrine\UuidableTrait;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AttachmentRepository")
 */
class Attachment implements UuidableInterface, TimeableInterface
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
     * @var string|null Nombre original del adjunto
     * @ORM\Column(type="string")
     */
    private $filename;

    /**
     * @var UuidInterface|null Nombre del adjunto en forma de identificador único
     * @ORM\Column(type="uuid", unique=true)
     */
    private $path;

    /**
     * @var Message|null Mensaje al que pertenece el adjunto
     * @ORM\ManyToOne(targetEntity="Message", inversedBy="attachments")
     * @ORM\JoinColumn(name="message_id", referencedColumnName="id")
     */
    private $message;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(?string $filename): self
    {
        $this->filename = $filename;
        return $this;
    }

    public function getPath(): ?UuidInterface
    {
        return $this->path;
    }

    function setPath(?UuidInterface $path): self
    {
        $this->path = $path;
        return $this;
    }

    public function getMessage(): ?Message
    {
        return $this->message;
    }

    public function setMessage(?Message $message): self
    {
        $this->message = $message;
        return $this;
    }
}
