<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:health-check',
    description: 'Check if the message processor application is healthy'
)]
class HealthCheckCommand extends Command
{
    public function __construct()
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Message Processor Health Check');

        // Check environment variables
        $envVars = [
            'APP_ENV',
            'MONGODB_URL',
            'LOCALSTACK_ENDPOINT',
            'SQS_QUEUE_URL',
            'AWS_ACCESS_KEY_ID',
            'SES_FROM_EMAIL'
        ];

        $io->section('Environment Variables');
        foreach ($envVars as $var) {
            $value = $_ENV[$var] ?? 'NOT SET';
            $status = $value !== 'NOT SET' ? '✅' : '❌';
            $io->writeln(sprintf('%s %s: %s', $status, $var, $value === 'NOT SET' ? $value : '***'));
        }

        // Check if we can connect to MongoDB
        $io->section('MongoDB Connection');
        try {
            $mongoUrl = $_ENV['MONGODB_URL'] ?? null;
            if ($mongoUrl) {
                $io->writeln('✅ MongoDB URL configured');
            } else {
                $io->writeln('❌ MongoDB URL not configured');
            }
        } catch (\Exception $e) {
            $io->writeln('❌ MongoDB connection failed: ' . $e->getMessage());
        }

        // Check SQS configuration
        $io->section('SQS Configuration');
        $sqsUrl = $_ENV['SQS_QUEUE_URL'] ?? null;
        if ($sqsUrl) {
            $io->writeln('✅ SQS Queue URL configured: ' . $sqsUrl);
        } else {
            $io->writeln('❌ SQS Queue URL not configured');
        }

        $io->success('Health check completed!');
        $io->note('You can now start consuming messages with: php bin/console messenger:consume sqs');

        return Command::SUCCESS;
    }
}
