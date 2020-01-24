<?php

namespace App\Util\Doctrine;

use Ramsey\Uuid\UuidInterface;

interface UuidableInterface
{
    /**
     * @return UuidInterface|null
     */
    public function getUuid(): ?UuidInterface;

    /**
     * @param UuidInterface|null $uuid
     */
    public function setUuid(?UuidInterface $uuid): void;
}
