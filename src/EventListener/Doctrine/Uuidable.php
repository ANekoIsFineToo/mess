<?php

namespace App\EventListener\Doctrine;

use App\Util\Doctrine\UuidableInterface;
use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Ramsey\Uuid\Uuid;

class Uuidable
{
    public function prePersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof UuidableInterface)
        {
            return;
        }

        $entity->setUuid(Uuid::uuid4());
    }
}
