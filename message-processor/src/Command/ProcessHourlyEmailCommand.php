<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\EmailService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Psr\Log\LoggerInterface;

#[AsCommand(
    name: 'app:process-hourly-email',
    description: 'Process hourly counter events and send email report via SES'
)]
// console command for triggering hourly email report
class ProcessHourlyEmailCommand extends Command
{
    public function __construct(
        private EmailService $emailService,
        private LoggerInterface $logger
    ) {
        parent::__construct();
    }

    // here we run the email service and handle success or failure of it
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Processing Hourly Counter Email Report');

        try {
            $io->info('Sending hourly counter report...');
            
            $success = $this->emailService->sendHourlyReport();

            if ($success) {
                $io->success('Hourly counter report sent successfully!');
                $this->logger->info('Hourly email report command completed successfully');
                return Command::SUCCESS;
            } else {
                $io->error('Failed to send hourly counter report');
                $this->logger->error('Hourly email report command failed');
                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $io->error('An error occurred while processing the hourly email report: ' . $e->getMessage());
            $this->logger->error('Hourly email report command failed with exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return Command::FAILURE;
        }
    }
}
