<?php

declare(strict_types=1);

namespace App\Message;

final class CounterIncrementMessage
{
    public function __construct(
        public readonly string $eventType,
        public readonly string $timestamp,
        public readonly array $metadata = []
    ) {}
}
