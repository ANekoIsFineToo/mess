<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 */
class ThreadRead
{
    /**
     * @var User|null Usuario que ha leído la conversación
     * @ORM\Id()
     * @ORM\ManyToOne(targetEntity="User", inversedBy="reads")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     */
    private $user;

    /**
     * @var Thread|null Conversación que ha leído el usuario
     * @ORM\Id()
     * @ORM\ManyToOne(targetEntity="Thread", inversedBy="reads")
     * @ORM\JoinColumn(name="thread_id", referencedColumnName="id")
     */
    private $thread;

    /**
     * @var DateTime|null Fecha de la última lectura de la conversación por el usuario
     * @ORM\Column(name="last_read_at", type="datetime")
     */
    private $lastReadAt;

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
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

    public function getLastReadAt(): ?DateTime
    {
        return $this->lastReadAt;
    }

    public function setLastReadAt(?DateTime $lastReadAt): self
    {
        $this->lastReadAt = $lastReadAt;
        return $this;
    }
}
