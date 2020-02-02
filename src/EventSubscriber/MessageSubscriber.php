<?php

namespace App\EventSubscriber;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Address;

class MessageSubscriber implements EventSubscriberInterface
{
    /** @var ParameterBagInterface $params */
    private $params;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }

    public function onMessage(MessageEvent $messageEvent): void
    {
        $messageEvent->getEnvelope()->setSender(Address::fromString($this->params->get('from_email')));
    }

    public static function getSubscribedEvents()
    {
        return [
            MessageEvent::class => 'onMessage'
        ];
    }
}
