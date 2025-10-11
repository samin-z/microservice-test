<?php

declare(strict_types=1);

namespace App\Message;

final class CounterIncrementMessage
{
    // defining the structure of the SQS message that is sent to the queue (its coming from counter-api)
    public function __construct(
        public readonly string $eventType,
        public readonly string $timestamp,
        public readonly array $metadata = []
    ) {}
}
