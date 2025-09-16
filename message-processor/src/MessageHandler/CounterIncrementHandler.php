<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Document\CounterEvent;
use App\Message\CounterIncrementMessage;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(fromTransport: 'sqs')]
final class CounterIncrementHandler
{
    public function __construct(private readonly DocumentManager $dm)
    {
    }

    public function __invoke(CounterIncrementMessage $message): void
    {
        $event = new CounterEvent();
        $event->setEventType($message->eventType);
        $event->setTimestamp(new \DateTime($message->timestamp));
        $event->setCreatedAt(new \DateTime());
        $event->setMetadata($message->metadata);

        $this->dm->persist($event);
        $this->dm->flush();
    }
}


