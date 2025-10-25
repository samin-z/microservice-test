<?php

declare(strict_types=1);

namespace App\Tests\MessageHandler;

use App\Document\CounterEvent;
use App\Message\CounterIncrementMessage;
use App\MessageHandler\CounterIncrementHandler;
use Doctrine\ODM\MongoDB\DocumentManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class CounterIncrementHandlerTest extends TestCase
{
    private CounterIncrementHandler $handler;
    private MockObject|DocumentManager $documentManager;

    protected function setUp(): void
    {
        $this->documentManager = $this->createMock(DocumentManager::class);
        $this->handler = new CounterIncrementHandler($this->documentManager);
    }

    public function testHandlerInvocationWithValidMessage(): void
    {
        $message = new CounterIncrementMessage(
            eventType: 'counter_increment',
            timestamp: '2025-01-15 10:30:00',
            metadata: ['user_id' => '123', 'source' => 'api']
        );

        $this->documentManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(CounterEvent::class));

        $this->documentManager->expects($this->once())
            ->method('flush');

        $this->handler->__invoke($message);
    }

    public function testHandlerCreatesCounterEventWithCorrectData(): void
    {
        $message = new CounterIncrementMessage(
            eventType: 'counter_increment',
            timestamp: '2025-01-15 10:30:00',
            metadata: ['user_id' => '123', 'source' => 'api']
        );

        $persistedEvent = null;
        $this->documentManager->expects($this->once())
            ->method('persist')
            ->willReturnCallback(function ($event) use (&$persistedEvent) {
                $persistedEvent = $event;
            });

        $this->documentManager->expects($this->once())
            ->method('flush');

        $this->handler->__invoke($message);

        $this->assertInstanceOf(CounterEvent::class, $persistedEvent);
        $this->assertEquals('counter_increment', $persistedEvent->getEventType());
        $this->assertEquals('2025-01-15 10:30:00', $persistedEvent->getTimestamp()->format('Y-m-d H:i:s'));
        $this->assertEquals(['user_id' => '123', 'source' => 'api'], $persistedEvent->getMetadata());
        $this->assertInstanceOf(\DateTime::class, $persistedEvent->getCreatedAt());
    }

    public function testHandlerWithEmptyMetadata(): void
    {
        $message = new CounterIncrementMessage(
            eventType: 'counter_increment',
            timestamp: '2025-01-15 10:30:00',
            metadata: []
        );

        $persistedEvent = null;
        $this->documentManager->expects($this->once())
            ->method('persist')
            ->willReturnCallback(function ($event) use (&$persistedEvent) {
                $persistedEvent = $event;
            });

        $this->documentManager->expects($this->once())
            ->method('flush');

        $this->handler->__invoke($message);

        $this->assertInstanceOf(CounterEvent::class, $persistedEvent);
        $this->assertEquals('counter_increment', $persistedEvent->getEventType());
        $this->assertEquals([], $persistedEvent->getMetadata());
    }

    public function testHandlerWithComplexMetadata(): void
    {
        $complexMetadata = [
            'user_id' => '123',
            'session_id' => 'abc-def-ghi',
            'request_id' => 'req-456',
            'nested' => [
                'level1' => [
                    'level2' => 'value'
                ]
            ],
            'tags' => ['tag1', 'tag2', 'tag3']
        ];

        $message = new CounterIncrementMessage(
            eventType: 'counter_increment',
            timestamp: '2025-01-15 10:30:00',
            metadata: $complexMetadata
        );

        $persistedEvent = null;
        $this->documentManager->expects($this->once())
            ->method('persist')
            ->willReturnCallback(function ($event) use (&$persistedEvent) {
                $persistedEvent = $event;
            });

        $this->documentManager->expects($this->once())
            ->method('flush');

        $this->handler->__invoke($message);

        $this->assertInstanceOf(CounterEvent::class, $persistedEvent);
        $this->assertEquals($complexMetadata, $persistedEvent->getMetadata());
    }

    public function testHandlerWithDifferentEventTypes(): void
    {
        $eventTypes = [
            'counter_increment',
            'counter_decrement',
            'counter_reset',
            'user_action',
            'system_event'
        ];

        foreach ($eventTypes as $eventType) {
            // Create a new handler and document manager for each iteration
            $documentManager = $this->createMock(DocumentManager::class);
            $handler = new CounterIncrementHandler($documentManager);
            
            $message = new CounterIncrementMessage(
                eventType: $eventType,
                timestamp: '2025-01-15 10:30:00',
                metadata: ['type' => $eventType]
            );

            $persistedEvent = null;
            $documentManager->expects($this->once())
                ->method('persist')
                ->willReturnCallback(function ($event) use (&$persistedEvent) {
                    $persistedEvent = $event;
                });

            $documentManager->expects($this->once())
                ->method('flush');

            $handler->__invoke($message);

            $this->assertEquals($eventType, $persistedEvent->getEventType());
        }
    }

    public function testHandlerSetsCreatedAtToCurrentTime(): void
    {
        $beforeInvocation = new \DateTime();
        
        $message = new CounterIncrementMessage(
            eventType: 'counter_increment',
            timestamp: '2025-01-15 10:30:00',
            metadata: []
        );

        $persistedEvent = null;
        $this->documentManager->expects($this->once())
            ->method('persist')
            ->willReturnCallback(function ($event) use (&$persistedEvent) {
                $persistedEvent = $event;
            });

        $this->documentManager->expects($this->once())
            ->method('flush');

        $this->handler->__invoke($message);

        $afterInvocation = new \DateTime();

        $this->assertInstanceOf(\DateTime::class, $persistedEvent->getCreatedAt());
        $this->assertGreaterThanOrEqual($beforeInvocation, $persistedEvent->getCreatedAt());
        $this->assertLessThanOrEqual($afterInvocation, $persistedEvent->getCreatedAt());
    }

    public function testHandlerConstructorInjection(): void
    {
        $this->assertInstanceOf(CounterIncrementHandler::class, $this->handler);
    }
}
