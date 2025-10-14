<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\ProcessHourlyEmailCommand;
use App\Service\EmailService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ProcessHourlyEmailCommandTest extends TestCase
{
    private ProcessHourlyEmailCommand $command;
    private MockObject|EmailService $emailService;
    private MockObject|LoggerInterface $logger;
    private MockObject|InputInterface $input;
    private MockObject|OutputInterface $output;

    protected function setUp(): void
    {
        $this->emailService = $this->createMock(EmailService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->input = $this->createMock(InputInterface::class);
        $this->output = $this->createMock(OutputInterface::class);

        $this->command = new ProcessHourlyEmailCommand(
            $this->emailService,
            $this->logger
        );
    }

    public function testExecuteSuccess(): void
    {
        $this->emailService->expects($this->once())
            ->method('sendHourlyReport')
            ->willReturn(true);

        $this->logger->expects($this->once())
            ->method('info')
            ->with('Hourly email report command completed successfully');

        $this->mockSymfonyStyleOutput();

        $result = $this->command->run($this->input, $this->output);

        $this->assertEquals(Command::SUCCESS, $result);
    }

    public function testExecuteFailure(): void
    {
        $this->emailService->expects($this->once())
            ->method('sendHourlyReport')
            ->willReturn(false);

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Hourly email report command failed');

        // mock SymfonyStyle to capture output
        $this->mockSymfonyStyleOutput();

        $result = $this->command->run($this->input, $this->output);

        $this->assertEquals(Command::FAILURE, $result);
    }

    public function testExecuteException(): void
    {
        $exception = new \Exception('Test exception message');
        
        $this->emailService->expects($this->once())
            ->method('sendHourlyReport')
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                'Hourly email report command failed with exception',
                $this->callback(function ($context) {
                    return isset($context['error']) && isset($context['trace']);
                })
            );

        // mock SymfonyStyle to capture output
        $this->mockSymfonyStyleOutput();

        $result = $this->command->run($this->input, $this->output);

        $this->assertEquals(Command::FAILURE, $result);
    }

    public function testCommandName(): void
    {
        $this->assertEquals('app:process-hourly-email', $this->command->getName());
    }

    public function testCommandDescription(): void
    {
        $this->assertEquals(
            'Process hourly counter events and send email report via SES',
            $this->command->getDescription()
        );
    }

    private function mockSymfonyStyleOutput(): void
    {
        // mock the SymfonyStyle methods that would be called
        $this->output->method('isVerbose')->willReturn(false);
        $this->output->method('isVeryVerbose')->willReturn(false);
        $this->output->method('isDebug')->willReturn(false);
        $this->output->method('isDecorated')->willReturn(false);
        $this->output->method('getVerbosity')->willReturn(OutputInterface::VERBOSITY_NORMAL);
        $this->output->method('write')->willReturnCallback(function() {});
        $this->output->method('writeln')->willReturnCallback(function() {});
    }
}
