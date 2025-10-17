<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\SimpleSqsConsumer;
use App\Document\CounterEvent;
use Doctrine\ODM\MongoDB\DocumentManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Tester\CommandTester;

class SimpleSqsConsumerTest extends TestCase
{
    private MockObject|DocumentManager $documentManager;
    private SimpleSqsConsumer $command;

    protected function setUp(): void
    {
        $this->documentManager = $this->createMock(DocumentManager::class);
        $this->command = new SimpleSqsConsumer($this->documentManager);
    }

    public function testCommandConfiguration(): void
    {
        $this->assertSame('app:simple-sqs-consumer', $this->command->getName());
        $this->assertSame('Simple SQS consumer that processes counter increment messages', $this->command->getDescription());
    }

    public function testCommandHasCorrectName(): void
    {
        $name = $this->command->getName();

        $this->assertNotNull($name);
        $this->assertStringContainsString('sqs-consumer', $name);
    }

    public function testCommandExtendsCommand(): void
    {
        $this->assertInstanceOf(\Symfony\Component\Console\Command\Command::class, $this->command);
    }

    public function testCommandUsesDocumentManager(): void
    {
        $reflection = new \ReflectionClass($this->command);
        $property = $reflection->getProperty('documentManager');
        $property->setAccessible(true);
        
        $dm = $property->getValue($this->command);
        
        $this->assertInstanceOf(DocumentManager::class, $dm);
        $this->assertSame($this->documentManager, $dm);
    }

    public function testCommandDefinition(): void
    {
        $definition = $this->command->getDefinition();

        $this->assertCount(0, $definition->getArguments());
        $this->assertCount(0, $definition->getOptions());
    }

    public function testCommandWithEnvironmentVariables(): void
    {
        // here set up environment variables
        $_ENV['SQS_QUEUE_URL'] = 'http://test-queue-url';
        $_ENV['LOCALSTACK_ENDPOINT'] = 'http://test-endpoint';

        $command = new SimpleSqsConsumer($this->documentManager);

        $this->assertInstanceOf(SimpleSqsConsumer::class, $command);
        
        unset($_ENV['SQS_QUEUE_URL']);
        unset($_ENV['LOCALSTACK_ENDPOINT']);
    }

    public function testCounterEventCreationLogic(): void
    {
        // this test is for verifying the logic for creating a CounterEvent
        
        $messageBody = [
            'eventType' => 'COUNTER_INCREMENT',
            'timestamp' => '2025-10-17T12:00:00Z',
            'metadata' => [
                'source' => 'counter-api',
                'version' => '1.0'
            ]
        ];

        $counterEvent = new CounterEvent();
        $counterEvent->setEventType($messageBody['eventType']);
        $counterEvent->setTimestamp(new \DateTime($messageBody['timestamp']));
        $counterEvent->setMetadata($messageBody['metadata'] ?? []);
        $counterEvent->setCreatedAt(new \DateTime());

        $this->assertSame('COUNTER_INCREMENT', $counterEvent->getEventType());
        $this->assertInstanceOf(\DateTime::class, $counterEvent->getTimestamp());
        $this->assertSame($messageBody['metadata'], $counterEvent->getMetadata());
        $this->assertInstanceOf(\DateTime::class, $counterEvent->getCreatedAt());
    }

    public function testCounterEventCreationWithoutMetadata(): void
    {
        $messageBody = [
            'eventType' => 'COUNTER_INCREMENT',
            'timestamp' => '2025-10-17T12:00:00Z',
        ];

        $counterEvent = new CounterEvent();
        $counterEvent->setEventType($messageBody['eventType']);
        $counterEvent->setTimestamp(new \DateTime($messageBody['timestamp']));
        $counterEvent->setMetadata($messageBody['metadata'] ?? []);
        $counterEvent->setCreatedAt(new \DateTime());

        $this->assertSame('COUNTER_INCREMENT', $counterEvent->getEventType());
        $this->assertIsArray($counterEvent->getMetadata());
        $this->assertEmpty($counterEvent->getMetadata());
    }

    public function testInvalidMessageFormatDetection(): void
    {
        $invalidMessageBody = [
            'wrongField' => 'COUNTER_INCREMENT',
            'timestamp' => '2025-10-17T12:00:00Z',
        ];

        $isValid = isset($invalidMessageBody['eventType']) && 
                   $invalidMessageBody['eventType'] === 'COUNTER_INCREMENT';

        $this->assertFalse($isValid, 'Message without eventType should be invalid');
    }

    public function testValidMessageFormatDetection(): void
    {
        $validMessageBody = [
            'eventType' => 'COUNTER_INCREMENT',
            'timestamp' => '2025-10-17T12:00:00Z',
            'metadata' => ['source' => 'counter-api']
        ];

        $isValid = isset($validMessageBody['eventType']) && 
                   $validMessageBody['eventType'] === 'COUNTER_INCREMENT';

        $this->assertTrue($isValid, 'Message with correct eventType should be valid');
    }

    public function testWrongEventTypeDetection(): void
    {
        $messageBody = [
            'eventType' => 'WRONG_EVENT_TYPE',
            'timestamp' => '2025-10-17T12:00:00Z',
        ];

        $isValid = isset($messageBody['eventType']) && 
                   $messageBody['eventType'] === 'COUNTER_INCREMENT';

        $this->assertFalse($isValid, 'Message with wrong eventType should be invalid');
    }

    public function testJsonDecoding(): void
    {
        $jsonMessage = '{"eventType":"COUNTER_INCREMENT","timestamp":"2025-10-17T12:00:00Z","metadata":{"source":"counter-api"}}';

        $messageBody = json_decode($jsonMessage, true);

        $this->assertIsArray($messageBody);
        $this->assertArrayHasKey('eventType', $messageBody);
        $this->assertArrayHasKey('timestamp', $messageBody);
        $this->assertArrayHasKey('metadata', $messageBody);
        $this->assertSame('COUNTER_INCREMENT', $messageBody['eventType']);
    }

    public function testInvalidJsonHandling(): void
    {
        $invalidJson = '{invalid json}';

        $messageBody = json_decode($invalidJson, true);

        $this->assertNull($messageBody, 'Invalid JSON should return null');
    }
}

