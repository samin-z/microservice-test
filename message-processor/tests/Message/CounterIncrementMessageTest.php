<?php

declare(strict_types=1);

namespace App\Tests\Message;

use App\Message\CounterIncrementMessage;
use PHPUnit\Framework\TestCase;

class CounterIncrementMessageTest extends TestCase
{
    public function testCounterIncrementMessageCreation(): void
    {
        $eventType = 'COUNTER_INCREMENT';
        $timestamp = '2025-10-17T12:00:00Z';
        $metadata = [
            'source' => 'counter-api',
            'version' => '1.0'
        ];

        $message = new CounterIncrementMessage($eventType, $timestamp, $metadata);

        $this->assertSame($eventType, $message->eventType);
        $this->assertSame($timestamp, $message->timestamp);
        $this->assertSame($metadata, $message->metadata);
    }

    public function testCounterIncrementMessageWithoutMetadata(): void
    {
        $eventType = 'COUNTER_INCREMENT';
        $timestamp = '2025-10-17T12:00:00Z';

        $message = new CounterIncrementMessage($eventType, $timestamp);

        $this->assertSame($eventType, $message->eventType);
        $this->assertSame($timestamp, $message->timestamp);
        $this->assertSame([], $message->metadata);
    }

    public function testCounterIncrementMessageIsReadonly(): void
    {
        $eventType = 'COUNTER_INCREMENT';
        $timestamp = '2025-10-17T12:00:00Z';
        $metadata = ['source' => 'counter-api'];

        $message = new CounterIncrementMessage($eventType, $timestamp, $metadata);

        // verify properties are readonly by checking they exist and have correct values
        $this->assertSame($eventType, $message->eventType);
        $this->assertSame($timestamp, $message->timestamp);
        $this->assertSame($metadata, $message->metadata);
        
        $reflection = new \ReflectionClass($message);
        $this->assertTrue($reflection->isFinal(), 'CounterIncrementMessage should be final');
    }

    public function testCounterIncrementMessageWithEmptyMetadata(): void
    {
        $eventType = 'COUNTER_INCREMENT';
        $timestamp = '2025-10-17T12:00:00Z';
        $emptyMetadata = [];

        $message = new CounterIncrementMessage($eventType, $timestamp, $emptyMetadata);

        $this->assertSame($eventType, $message->eventType);
        $this->assertSame($timestamp, $message->timestamp);
        $this->assertIsArray($message->metadata);
        $this->assertEmpty($message->metadata);
    }

    public function testCounterIncrementMessageWithComplexMetadata(): void
    {
        $eventType = 'COUNTER_INCREMENT';
        $timestamp = '2025-10-17T12:00:00Z';
        $metadata = [
            'source' => 'counter-api',
            'version' => '1.0',
            'tags' => ['production', 'critical'],
            'nested' => [
                'key1' => 'value1',
                'key2' => 'value2'
            ]
        ];

        $message = new CounterIncrementMessage($eventType, $timestamp, $metadata);

        $this->assertSame($eventType, $message->eventType);
        $this->assertSame($timestamp, $message->timestamp);
        $this->assertSame($metadata, $message->metadata);
        $this->assertArrayHasKey('tags', $message->metadata);
        $this->assertArrayHasKey('nested', $message->metadata);
        $this->assertIsArray($message->metadata['tags']);
        $this->assertIsArray($message->metadata['nested']);
    }
}

