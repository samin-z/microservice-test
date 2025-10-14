<?php

declare(strict_types=1);

namespace App\Service;

use Aws\Ses\SesClient;
use Aws\Exception\AwsException;
use Doctrine\ODM\MongoDB\DocumentManager;
use App\Document\CounterEvent;
use Psr\Log\LoggerInterface;
// this is the service that sends the hourly email report and handles email reports through AWS SES
class EmailService
{
    private SesClient $sesClient;
    private DocumentManager $documentManager;
    private LoggerInterface $logger;
    private string $fromEmail;
    private string $toEmail;

    public function __construct(
        DocumentManager $documentManager,
        LoggerInterface $logger,
        string $awsAccessKeyId,
        string $awsSecretAccessKey,
        string $awsRegion,
        string $awsEndpoint,
        string $fromEmail,
        string $toEmail
    ) {
        $this->documentManager = $documentManager;
        $this->logger = $logger;
        $this->fromEmail = $fromEmail;
        $this->toEmail = $toEmail;

        $this->sesClient = new SesClient([
            'version' => 'latest',
            'region' => $awsRegion,
            'credentials' => [
                'key' => $awsAccessKeyId,
                'secret' => $awsSecretAccessKey,
            ],
            'endpoint' => $awsEndpoint,
            'use_path_style_endpoint' => true,
        ]);
    }

    // this is the main function that sends hourly counter reports
    public function sendHourlyReport(): bool
    {
        try {
            // Get events from the last hour
            $oneHourAgo = new \DateTime('-1 hour');
            $events = $this->documentManager->getRepository(CounterEvent::class)
                ->createQueryBuilder()
                ->field('createdAt')->gte($oneHourAgo)
                ->sort('createdAt', 'asc')
                ->getQuery()
                ->execute();

            $eventCount = $events->count();
            $eventsArray = $events->toArray();

            if ($eventCount === 0) {
                $this->logger->info('No counter events found in the last hour, skipping email report');
                return true;
            }

            $firstEvent = $eventsArray[0];
            $lastEvent = $eventsArray[count($eventsArray) - 1];

            $reportData = [
                'total_increments' => $eventCount,
                'first_event_time' => $firstEvent->getTimestamp(),
                'last_event_time' => $lastEvent->getTimestamp(),
                'report_time' => new \DateTime(),
            ];

            $subject = sprintf(
                'Hourly Counter Report - %s',
                $reportData['report_time']->format('Y-m-d H:i')
            );

            $textBody = $this->generateTextEmail($reportData);
            $htmlBody = $this->generateHtmlEmail($reportData);

            $result = $this->sesClient->sendEmail([
                'Source' => $this->fromEmail,
                'Destination' => [
                    'ToAddresses' => [$this->toEmail],
                ],
                'Message' => [
                    'Subject' => [
                        'Data' => $subject,
                        'Charset' => 'UTF-8',
                    ],
                    'Body' => [
                        'Text' => [
                            'Data' => $textBody,
                            'Charset' => 'UTF-8',
                        ],
                        'Html' => [
                            'Data' => $htmlBody,
                            'Charset' => 'UTF-8',
                        ],
                    ],
                ],
            ]);

            $this->logger->info('Hourly counter report email sent successfully', [
                'message_id' => $result['MessageId'],
                'total_events' => $eventCount
            ]);

            // Clear processed events (optional - you might want to keep them for longer)
            $this->clearProcessedEvents($eventsArray);

            return true;

        } catch (AwsException $e) {
            $this->logger->error('Failed to send hourly counter report email', [
                'error' => $e->getAwsErrorMessage(),
                'error_code' => $e->getAwsErrorCode(),
            ]);
            return false;
        } catch (\Exception $e) {
            $this->logger->error('Unexpected error sending hourly counter report email', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    // this function creates plain email template
    public function generateTextEmail(array $data): string
    {
        return sprintf(
            "Counter Activity Summary\n" .
            "=========================\n\n" .
            "Report generated: %s UTC\n" .
            "Total increments processed: %d\n\n" .
            "Time Range:\n" .
            "- First event: %s UTC\n" .
            "- Last event: %s UTC\n\n" .
            "All events have been processed and cleared from the system.\n\n" .
            "--\n" .
            "This is an automated message from the Counter Message Processor service.",
            $data['report_time']->format('Y-m-d H:i:s'),
            $data['total_increments'],
            $data['first_event_time']->format('H:i:s'),
            $data['last_event_time']->format('H:i:s')
        );
    }

    // this function creates html email template
    public function generateHtmlEmail(array $data): string
    {
        $reportTime = $data['report_time']->format('Y-m-d H:i:s');
        $totalIncrements = $data['total_increments'];
        $firstEventTime = $data['first_event_time']->format('H:i:s');
        $lastEventTime = $data['last_event_time']->format('H:i:s');

        return sprintf(
            '<!DOCTYPE html>
<html>
<head>
    <title>Hourly Counter Report</title>
</head>
<body>
    <div style="font-family: Arial, sans-serif; max-width: 500px; margin: 0 auto;">
        <h2 style="color: #333;">Counter Activity Summary</h2>
        
        <div style="background-color: #f5f5f5; padding: 15px; border-radius: 5px; margin: 10px;">
            <p><strong>Report generated:</strong> %s UTC</p>
            <p><strong>Total increments processed:</strong> <span style="color: #007bff; font-size: 18px;">%d</span></p>
            
            <h3 style="color: #333;">Time Range</h3>
            <p><strong>First event:</strong> %s UTC</p>
            <p><strong>Last event:</strong> %s UTC</p>
        </div>
        
        <p style="margin-top: 20px; color: #666; font-size: 14px;">
            All events have been processed and cleared from the system.
        </p>
        
        <hr style="margin: 20px 0; border: none; border-top: 1px solid #ddd;"/>
        
        <p style="color: #999; font-size: 12px;">
            This is an automated message from the Counter Message Processor service.
        </p>
    </div>
</body>
</html>',
            $reportTime,
            $totalIncrements,
            $firstEventTime,
            $lastEventTime
        );
    }

    // this is the function for removing processes events from database
    private function clearProcessedEvents(array $events): void
    {
        foreach ($events as $event) {
            $this->documentManager->remove($event);
        }
        $this->documentManager->flush();
        
        $this->logger->info('Cleared processed counter events from database', [
            'cleared_count' => count($events)
        ]);
    }
}
