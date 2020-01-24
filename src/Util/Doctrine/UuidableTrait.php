<?php

namespace App\Util\Doctrine;

use Ramsey\Uuid\UuidInterface;

trait UuidableTrait
{
    /**
     * Identificador único externo de la entidad. Usado para peticiones públicas.
     *
     * @var UuidInterface|null
     *
     * @ORM\Column(type="uuid", unique=true)
     */
    private $uuid;

    /**
     * @return UuidInterface|null
     */
    public function getUuid(): ?UuidInterface
    {
        return $this->uuid;
    }

    /**
     * @param UuidInterface|null $uuid
     */
    public function setUuid(?UuidInterface $uuid): void
    {
        $this->uuid = $uuid;
    }
}
