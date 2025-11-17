<?php

declare(strict_types=1);

namespace App\Command;

use Sentry\State\HubInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-sentry',
    description: 'Test Sentry error tracking by triggering different types of errors'
)]
class TestSentryCommand extends Command
{
    public function __construct(
        private readonly ?HubInterface $sentryHub = null
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'type',
                't',
                InputOption::VALUE_REQUIRED,
                'Type of error to trigger: exception, message, context, or all',
                'exception'
            )
            ->setHelp('This command helps you test Sentry integration by triggering different types of errors.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $type = $input->getOption('type');

        if ($this->sentryHub === null) {
            $io->warning('Sentry Hub is not available. Make sure SENTRY_DSN is configured.');
            return Command::FAILURE;
        }

        $io->title('Sentry Test Command');
        $io->info('This will send test errors to your Sentry dashboard.');

        switch ($type) {
            case 'exception':
                $this->testException($io);
                break;
            case 'message':
                $this->testMessage($io);
                break;
            case 'context':
                $this->testWithContext($io);
                break;
            case 'all':
                $this->testException($io);
                $this->testMessage($io);
                $this->testWithContext($io);
                break;
            default:
                $io->error("Unknown test type: $type. Use: exception, message, context, or all");
                return Command::FAILURE;
        }

        $io->success('Test errors sent to Sentry! Check your dashboard at https://sentry.io');
        $io->note('It may take a few seconds for errors to appear in your dashboard.');

        return Command::SUCCESS;
    }

    private function testException(SymfonyStyle $io): void
    {
        $io->section('Testing: Exception Capture');

        try {
            throw new \RuntimeException('This is a test exception to verify Sentry integration');
        } catch (\Exception $e) {
            $this->sentryHub->withScope(function (\Sentry\State\Scope $scope) use ($e): void {
                $scope->setTags([
                    'test_type' => 'exception',
                    'command' => 'test-sentry',
                ]);
                $this->sentryHub->captureException($e);
            });

            $io->text('✓ Exception captured and sent to Sentry');
        }
    }

    private function testMessage(SymfonyStyle $io): void
    {
        $io->section('Testing: Message Capture');

        $this->sentryHub->withScope(function (\Sentry\State\Scope $scope): void {
            $scope->setTags([
                'test_type' => 'message',
                'command' => 'test-sentry',
            ]);
            $scope->setExtra('test_data', 'This is additional context data');
            $scope->setExtra('timestamp', date('Y-m-d H:i:s'));
            $this->sentryHub->captureMessage(
                'This is a test message to verify Sentry integration',
                \Sentry\Severity::warning()
            );
        });

        $io->text('✓ Message captured and sent to Sentry');
    }

    private function testWithContext(SymfonyStyle $io): void
    {
        $io->section('Testing: Exception with Rich Context');

        try {
            throw new \InvalidArgumentException('Test error with context data');
        } catch (\Exception $e) {
            $this->sentryHub->withScope(function (\Sentry\State\Scope $scope) use ($e): void {
                $scope->setTags([
                    'test_type' => 'context',
                    'command' => 'test-sentry',
                    'service' => 'message-processor',
                ]);
                $scope->setContext('test', [
                    'environment' => $_ENV['APP_ENV'] ?? 'unknown',
                    'php_version' => PHP_VERSION,
                    'test_timestamp' => date('Y-m-d H:i:s'),
                ]);
                $scope->setExtra('test_scenario', 'Testing Sentry integration with rich context');
                $scope->setExtra('user_action', 'Manual test via console command');
                $scope->setUser([
                    'id' => 'test-user',
                    'username' => 'console-tester',
                ]);
                $this->sentryHub->captureException($e);
            });

            $io->text('✓ Exception with context captured and sent to Sentry');
        }
    }
}

