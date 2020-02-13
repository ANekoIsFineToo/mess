<?php

namespace App\EventListener\Doctrine;

use App\Util\Doctrine\TimeableInterface;
use DateTime;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;

class Timeable
{
    public function prePersist(LifecycleEventArgs $args): void
    {
        $this->setTimestamps($args);
    }

    public function preUpdate(LifecycleEventArgs $args): void
    {
        $this->setTimestamps($args);
    }

    private function setTimestamps(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof TimeableInterface)
        {
            return;
        }

        $now = new DateTime();

        $entity->setUpdatedAt($now);

        if ($entity->getCreatedAt() === null)
        {
            $entity->setCreatedAt($now);
        }
    }
}
