<?php
declare(strict_types=1);

namespace App\Command;

use App\Document\CounterEvent;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:simple-sqs-consumer',
    description: 'Simple SQS consumer that processes counter increment messages',
)]
// it consumes SQS messages and saves them to mongoDB
class SimpleSqsConsumer extends Command
{
    public function __construct(
        private readonly DocumentManager $documentManager
    ) {
        parent::__construct();
    }
    /* its a loop that polls SQS for messages and saves them to mongoDB
    process json messages and creates documents for CounterEvent
    if the messages from SQS queue are processed, they are deleted from the queue
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $io->title('Simple SQS Consumer');
        $io->note('This consumer will process SQS messages directly without Symfony Messenger');
        
        // getting SQS configuration
        $queueUrl = $_ENV['SQS_QUEUE_URL'] ?? 'http://localstack:4566/000000000000/counter-increment-queue';
        $awsEndpoint = $_ENV['LOCALSTACK_ENDPOINT'] ?? 'http://localstack:4566';
        
        $io->info("Queue URL: $queueUrl");
        $io->info("AWS Endpoint: $awsEndpoint");
        
        //  SQS client configuration
        $sqsConfig = [
            'version' => 'latest',
            'region' => 'us-east-1',
            'endpoint' => $awsEndpoint,
            'credentials' => [
                'key' => 'test',
                'secret' => 'test',
            ],
        ];
        
        try {
            $sqsClient = new \Aws\Sqs\SqsClient($sqsConfig);
            
            $io->success('SQS client created successfully');
            

            $io->note('Polling for messages... (Press Ctrl+C to stop)');
            
            while (true) {
                try {
                    $result = $sqsClient->receiveMessage([
                        'QueueUrl' => $queueUrl,
                        'MaxNumberOfMessages' => 1,
                        'WaitTimeSeconds' => 20, 
                    ]);
                    
                    if (!empty($result['Messages'])) {
                        foreach ($result['Messages'] as $message) {
                            $io->info('Received message: ' . $message['MessageId']);
                            
                            // parsed json message from kotlin
                            $messageBody = json_decode($message['Body'], true);
                            
                            if ($messageBody && isset($messageBody['eventType']) && $messageBody['eventType'] === 'COUNTER_INCREMENT') {
                                // creating and saving counter event, here we map JSON fields to counterEvent object
                                $counterEvent = new CounterEvent();
                                $counterEvent->setEventType($messageBody['eventType']);
                                $counterEvent->setTimestamp(new \DateTime($messageBody['timestamp']));
                                $counterEvent->setMetadata($messageBody['metadata'] ?? []);
                                $counterEvent->setCreatedAt(new \DateTime());
                                
                                $this->documentManager->persist($counterEvent);
                                $this->documentManager->flush();
                                
                                $io->success('Counter event saved to MongoDB: ' . $counterEvent->getId());
                                
                                // deletea the message from the queue
                                $sqsClient->deleteMessage([
                                    'QueueUrl' => $queueUrl,
                                    'ReceiptHandle' => $message['ReceiptHandle'],
                                ]);
                                
                                $io->info('Message deleted from queue');
                            } else {
                                $io->warning('Invalid message format: ' . $message['Body']);
                            }
                        }
                    } else {
                        $io->comment('No messages received, waiting...');
                    }
                } catch (\Exception $e) {
                    $io->error('Error processing message: ' . $e->getMessage());
                    sleep(5);
                }
            }
            
        } catch (\Exception $e) {
            $io->error('Failed to create SQS client: ' . $e->getMessage());
            return Command::FAILURE;
        }
        
        return Command::SUCCESS;
    }
}
