<?php

declare(strict_types=1);

namespace App\Tests\Document;

use App\Document\CounterEvent;
use PHPUnit\Framework\TestCase;

class CounterEventTest extends TestCase
{
    public function testCounterEventCreation(): void
    {
        $event = new CounterEvent();

        $this->assertNull($event->getId());
        $this->assertEmpty($event->getMetadata());
        
        // testing that typed properties are not accessible before initialization
        $this->expectException(\Error::class);
        $event->getEventType();
    }

    public function testSetEventType(): void
    {
        $event = new CounterEvent();
        $eventType = 'COUNTER_INCREMENT';

        $result = $event->setEventType($eventType);

        $this->assertSame($event, $result);
        $this->assertEquals($eventType, $event->getEventType());
    }

    public function testSetTimestamp(): void
    {
        $event = new CounterEvent();
        $timestamp = new \DateTime('2025-10-10 12:00:00');

        $result = $event->setTimestamp($timestamp);

        $this->assertSame($event, $result);
        $this->assertSame($timestamp, $event->getTimestamp());
    }

    public function testSetCreatedAt(): void
    {
        $event = new CounterEvent();
        $createdAt = new \DateTime('2025-10-10 12:05:00');

        $result = $event->setCreatedAt($createdAt);

        $this->assertSame($event, $result);
        $this->assertSame($createdAt, $event->getCreatedAt());
    }

    public function testSetMetadata(): void
    {
        $event = new CounterEvent();
        $metadata = [
            'source' => 'counter-api',
            'version' => '1.0',
            'user_agent' => 'test-agent'
        ];

        $result = $event->setMetadata($metadata);

        $this->assertSame($event, $result);
        $this->assertEquals($metadata, $event->getMetadata());
    }

    public function testSetEmptyMetadata(): void
    {
        $event = new CounterEvent();

        $result = $event->setMetadata([]);

        $this->assertSame($event, $result);
        $this->assertEmpty($event->getMetadata());
    }

    public function testFullCounterEventWorkflow(): void
    {
        $event = new CounterEvent();
        $timestamp = new \DateTime('2025-10-10 12:00:00');
        $createdAt = new \DateTime('2025-10-10 12:00:05');
        $metadata = [
            'source' => 'counter-api',
            'version' => '1.0',
            'ip_address' => '192.168.1.1'
        ];

        $event->setEventType('COUNTER_INCREMENT')
              ->setTimestamp($timestamp)
              ->setCreatedAt($createdAt)
              ->setMetadata($metadata);

        $this->assertEquals('COUNTER_INCREMENT', $event->getEventType());
        $this->assertSame($timestamp, $event->getTimestamp());
        $this->assertSame($createdAt, $event->getCreatedAt());
        $this->assertEquals($metadata, $event->getMetadata());
        
        // test metadata access
        $this->assertEquals('counter-api', $event->getMetadata()['source']);
        $this->assertEquals('1.0', $event->getMetadata()['version']);
        $this->assertEquals('192.168.1.1', $event->getMetadata()['ip_address']);
    }

    public function testEventTypeValidation(): void
    {
        $event = new CounterEvent();
        
        // test different event types
        $eventTypes = [
            'COUNTER_INCREMENT',
            'COUNTER_RESET',
            'COUNTER_DECREMENT',
            'SYSTEM_EVENT'
        ];
        
        foreach ($eventTypes as $eventType) {
            $event->setEventType($eventType);
            $this->assertEquals($eventType, $event->getEventType());
        }
    }

    public function testTimestampHandling(): void
    {
        $event = new CounterEvent();
        
        // test with different timezone
        $utcTime = new \DateTime('2025-10-10 12:00:00', new \DateTimeZone('UTC'));
        $localTime = new \DateTime('2025-10-10 12:00:00', new \DateTimeZone('America/New_York'));
        
        $event->setTimestamp($utcTime);
        $this->assertSame($utcTime, $event->getTimestamp());
        
        $event->setTimestamp($localTime);
        $this->assertSame($localTime, $event->getTimestamp());
    }

    public function testMetadataComplexStructure(): void
    {
        $event = new CounterEvent();
        $complexMetadata = [
            'source' => 'counter-api',
            'version' => '1.0',
            'nested' => [
                'key1' => 'value1',
                'key2' => ['sub1', 'sub2']
            ],
            'numbers' => [1, 2, 3],
            'boolean' => true,
            'null_value' => null
        ];

        $event->setMetadata($complexMetadata);

        $this->assertEquals($complexMetadata, $event->getMetadata());
        $this->assertEquals('value1', $event->getMetadata()['nested']['key1']);
        $this->assertEquals(['sub1', 'sub2'], $event->getMetadata()['nested']['key2']);
        $this->assertEquals([1, 2, 3], $event->getMetadata()['numbers']);
        $this->assertTrue($event->getMetadata()['boolean']);
        $this->assertNull($event->getMetadata()['null_value']);
    }
}
