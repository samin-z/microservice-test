<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\EmailService;
use App\Document\CounterEvent;
use Doctrine\ODM\MongoDB\DocumentManager;
use Aws\Ses\SesClient;
use Aws\Result;
use Aws\Exception\AwsException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class SimpleEmailServiceTest extends TestCase
{
    private EmailService $emailService;
    private MockObject|DocumentManager $documentManager;
    private MockObject|LoggerInterface $logger;
    private MockObject|SesClient $sesClient;

    protected function setUp(): void
    {
        $this->documentManager = $this->createMock(DocumentManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->sesClient = $this->createMock(SesClient::class);

        $this->emailService = new EmailService(
            $this->documentManager,
            $this->logger,
            'test-key',
            'test-secret',
            'us-east-1',
            'http://localhost:4566',
            'from@example.com',
            'to@example.com'
        );

        $reflection = new \ReflectionClass($this->emailService);
        $sesClientProperty = $reflection->getProperty('sesClient');
        $sesClientProperty->setAccessible(true);
        $sesClientProperty->setValue($this->emailService, $this->sesClient);
    }

    public function testGenerateTextEmail(): void
    {
        $reportTime = new \DateTime('2025-10-10 12:00:00');
        $firstEventTime = new \DateTime('2025-10-10 11:30:00');
        $lastEventTime = new \DateTime('2025-10-10 11:45:00');

        $data = [
            'total_increments' => 3,
            'first_event_time' => $firstEventTime,
            'last_event_time' => $lastEventTime,
            'report_time' => $reportTime,
        ];

        $result = $this->emailService->generateTextEmail($data);

        $this->assertStringContainsString('Counter Activity Summary', $result);
        $this->assertStringContainsString('Report generated: 2025-10-10 12:00:00 UTC', $result);
        $this->assertStringContainsString('Total increments processed: 3', $result);
        $this->assertStringContainsString('First event: 11:30:00 UTC', $result);
        $this->assertStringContainsString('Last event: 11:45:00 UTC', $result);
        $this->assertStringContainsString('Counter Message Processor service', $result);
    }

    public function testGenerateHtmlEmail(): void
    {
        $reportTime = new \DateTime('2025-10-10 12:00:00');
        $firstEventTime = new \DateTime('2025-10-10 11:30:00');
        $lastEventTime = new \DateTime('2025-10-10 11:45:00');

        $data = [
            'total_increments' => 3,
            'first_event_time' => $firstEventTime,
            'last_event_time' => $lastEventTime,
            'report_time' => $reportTime,
        ];

        $result = $this->emailService->generateHtmlEmail($data);

        $this->assertStringContainsString('<!DOCTYPE html>', $result);
        $this->assertStringContainsString('<title>Hourly Counter Report</title>', $result);
        $this->assertStringContainsString('Counter Activity Summary', $result);
        $this->assertStringContainsString('Report generated:', $result);
        $this->assertStringContainsString('Total increments processed:', $result);
        $this->assertStringContainsString('color: #007bff', $result);
        $this->assertStringContainsString('</html>', $result);
    }

    public function testEmailServiceConstructor(): void
    {
        // test to see if  EmailService can be instantiated with proper parameters
        $emailService = new EmailService(
            $this->documentManager,
            $this->logger,
            'test-key',
            'test-secret',
            'us-east-1',
            'http://localhost:4566',
            'from@example.com',
            'to@example.com'
        );

        $this->assertInstanceOf(EmailService::class, $emailService);
    }

    public function testCounterEventCreation(): void
    {
        $event = new CounterEvent();
        $event->setEventType('COUNTER_INCREMENT');
        $event->setTimestamp(new \DateTime('2025-10-10 12:00:00'));
        $event->setCreatedAt(new \DateTime('2025-10-10 12:00:05'));
        $event->setMetadata(['source' => 'counter-api', 'version' => '1.0']);

        $this->assertEquals('COUNTER_INCREMENT', $event->getEventType());
        $this->assertEquals('2025-10-10 12:00:00', $event->getTimestamp()->format('Y-m-d H:i:s'));
        $this->assertEquals('2025-10-10 12:00:05', $event->getCreatedAt()->format('Y-m-d H:i:s'));
        $this->assertEquals(['source' => 'counter-api', 'version' => '1.0'], $event->getMetadata());
    }
}
