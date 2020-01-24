<?php

namespace App\Util\Doctrine;

use DateTime;

interface TimeableInterface
{
    /**
     * @return DateTime|null
     */
    public function getCreatedAt(): ?DateTime;

    /**
     * @param DateTime|null $dateTime
     */
    public function setCreatedAt(?DateTime $dateTime): void;

    /**
     * @return DateTime|null
     */
    public function getUpdatedAt(): ?DateTime;

    /**
     * @param DateTime|null $dateTime
     */
    public function setUpdatedAt(?DateTime $dateTime): void;
}
