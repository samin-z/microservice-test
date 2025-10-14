<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\EmailService;
use App\Document\CounterEvent;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Query\Builder;
use Aws\Ses\SesClient;
use Aws\Result;
use Aws\Exception\AwsException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class EmailServiceTest extends TestCase
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

        // reflection is used to inject the mock SesClient
        $reflection = new \ReflectionClass($this->emailService);
        $sesClientProperty = $reflection->getProperty('sesClient');
        $sesClientProperty->setAccessible(true);
        $sesClientProperty->setValue($this->emailService, $this->sesClient);
    }

    public function testSendHourlyReportSuccess(): void
    {
        $now = new \DateTime();
        $oneHourAgo = new \DateTime('-1 hour');
        
        $event1 = new CounterEvent();
        $event1->setEventType('COUNTER_INCREMENT');
        $event1->setTimestamp(new \DateTime('-30 minutes'));
        $event1->setCreatedAt(new \DateTime('-30 minutes'));
        $event1->setMetadata(['source' => 'counter-api', 'version' => '1.0']);

        $event2 = new CounterEvent();
        $event2->setEventType('COUNTER_INCREMENT');
        $event2->setTimestamp(new \DateTime('-20 minutes'));
        $event2->setCreatedAt(new \DateTime('-20 minutes'));
        $event2->setMetadata(['source' => 'counter-api', 'version' => '1.0']);

        $events = [$event1, $event2];

        // mock MongoDB query builder
        $queryBuilder = $this->createMock(Builder::class);
        
        $queryBuilder->expects($this->once())
            ->method('field')
            ->with('createdAt')
            ->willReturnSelf();
            
        $queryBuilder->expects($this->once())
            ->method('gte')
            ->willReturnSelf();
            
        $queryBuilder->expects($this->once())
            ->method('sort')
            ->with('createdAt', 'asc')
            ->willReturnSelf();
            
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn(new \ArrayIterator($events));

        $this->documentManager->expects($this->once())
            ->method('getRepository')
            ->with(CounterEvent::class)
            ->willReturn($queryBuilder);

        // mock SES client
        $sesResult = new Result([
            'MessageId' => 'test-message-id-123',
        ]);

        $this->sesClient->expects($this->once())
            ->method('sendEmail')
            ->willReturn($sesResult);

        // mock document removal
        $this->documentManager->expects($this->exactly(2))
            ->method('remove')
            ->with($this->isInstanceOf(CounterEvent::class));
            
        $this->documentManager->expects($this->once())
            ->method('flush');

        // mock logger
        $this->logger->expects($this->once())
            ->method('info')
            ->with('Hourly counter report email sent successfully', $this->isType('array'));

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Cleared processed counter events from database', $this->isType('array'));

        $result = $this->emailService->sendHourlyReport();

        $this->assertTrue($result);
    }

    public function testSendHourlyReportNoEvents(): void
    {
        $queryBuilder = $this->createMock(Builder::class);
        
        $queryBuilder->expects($this->once())
            ->method('field')
            ->willReturnSelf();
            
        $queryBuilder->expects($this->once())
            ->method('gte')
            ->willReturnSelf();
            
        $queryBuilder->expects($this->once())
            ->method('sort')
            ->willReturnSelf();
            
        $queryBuilder->expects($this->once())
            ->method('getQuery')
            ->willReturn(new \ArrayIterator([]));

        $this->documentManager->expects($this->once())
            ->method('getRepository')
            ->with(CounterEvent::class)
            ->willReturn($queryBuilder);

        // mock logger for no events message
        $this->logger->expects($this->once())
            ->method('info')
            ->with('No counter events found in the last hour, skipping email report');

        $result = $this->emailService->sendHourlyReport();

        $this->assertTrue($result);
    }

    public function testSendHourlyReportSesFailure(): void
    {
        $event = new CounterEvent();
        $event->setEventType('COUNTER_INCREMENT');
        $event->setTimestamp(new \DateTime('-30 minutes'));
        $event->setCreatedAt(new \DateTime('-30 minutes'));

        $queryBuilder = $this->createMock(Builder::class);
        
        $queryBuilder->method('field')->willReturnSelf();
        $queryBuilder->method('gte')->willReturnSelf();
        $queryBuilder->method('sort')->willReturnSelf();
        $queryBuilder->method('getQuery')->willReturn(new \ArrayIterator([$event]));

        $this->documentManager->expects($this->once())
            ->method('getRepository')
            ->with(CounterEvent::class)
            ->willReturn($queryBuilder);

        // mock SES client failure
        $awsException = new AwsException('Email address not verified', new \Aws\Command('SendEmail'));

        $this->sesClient->expects($this->once())
            ->method('sendEmail')
            ->willThrowException($awsException);

        // mock logger for error
        $this->logger->expects($this->once())
            ->method('error')
            ->with('Failed to send hourly counter report email', $this->isType('array'));

        $result = $this->emailService->sendHourlyReport();

        $this->assertFalse($result);
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

        // here the public method is called directly
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

        //  the public method is called directly
        $result = $this->emailService->generateHtmlEmail($data);

        $this->assertStringContainsString('<!DOCTYPE html>', $result);
        $this->assertStringContainsString('<title>Hourly Counter Report</title>', $result);
        $this->assertStringContainsString('Counter Activity Summary', $result);
        $this->assertStringContainsString('Report generated:', $result);
        $this->assertStringContainsString('Total increments processed:', $result);
        $this->assertStringContainsString('First event: 11:30:00 UTC', $result);
        $this->assertStringContainsString('Last event: 11:45:00 UTC', $result);
        $this->assertStringContainsString('color: #007bff', $result);
        $this->assertStringContainsString('</html>', $result);
    }
}
