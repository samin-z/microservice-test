<?php

declare(strict_types=1);

namespace App\Tests\Command;

use App\Command\HealthCheckCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class HealthCheckCommandTest extends TestCase
{
    private HealthCheckCommand $command;
    private Application $application;

    protected function setUp(): void
    {
        $this->command = new HealthCheckCommand();
        $this->application = new Application();
        $this->application->add($this->command);
    }

    public function testCommandConfiguration(): void
    {
        $this->assertEquals('app:health-check', $this->command->getName());
        $this->assertEquals('Check if the message processor application is healthy', $this->command->getDescription());
    }

    public function testCommandExecutionWithMissingEnvVars(): void
    {
        // environment variables
        $envVars = [
            'APP_ENV',
            'MONGODB_URL',
            'LOCALSTACK_ENDPOINT',
            'SQS_QUEUE_URL',
            'AWS_ACCESS_KEY_ID',
            'SES_FROM_EMAIL'
        ];

        foreach ($envVars as $var) {
            unset($_ENV[$var]);
        }

        $input = new ArrayInput([]);
        $output = new BufferedOutput();

        $exitCode = $this->command->run($input, $output);
        $outputContent = $output->fetch();

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Message Processor Health Check', $outputContent);
        $this->assertStringContainsString('Environment Variables', $outputContent);
        $this->assertStringContainsString('MongoDB Connection', $outputContent);
        $this->assertStringContainsString('SQS Configuration', $outputContent);
        $this->assertStringContainsString('Health check completed!', $outputContent);
        $this->assertStringContainsString('NOT SET', $outputContent); // shows missing vars
    }

    public function testCommandExecutionWithAllEnvVars(): void
    {
        $_ENV['APP_ENV'] = 'test';
        $_ENV['MONGODB_URL'] = 'mongodb://localhost:27017/test';
        $_ENV['LOCALSTACK_ENDPOINT'] = 'http://localhost:4566';
        $_ENV['SQS_QUEUE_URL'] = 'http://localhost:4566/000000000000/counter-increment';
        $_ENV['AWS_ACCESS_KEY_ID'] = 'test-key';
        $_ENV['SES_FROM_EMAIL'] = 'test@example.com';

        $input = new ArrayInput([]);
        $output = new BufferedOutput();

        $exitCode = $this->command->run($input, $output);
        $outputContent = $output->fetch();

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Message Processor Health Check', $outputContent);
        $this->assertStringContainsString('Environment Variables', $outputContent);
        $this->assertStringContainsString('MongoDB Connection', $outputContent);
        $this->assertStringContainsString('SQS Configuration', $outputContent);
        $this->assertStringContainsString('Health check completed!', $outputContent);
        $this->assertStringContainsString('✅', $outputContent);
        $this->assertStringContainsString('MongoDB URL configured', $outputContent);
        $this->assertStringContainsString('SQS Queue URL configured', $outputContent);
    }

    public function testCommandExecutionWithPartialEnvVars(): void
    {
        $_ENV['APP_ENV'] = 'test';
        $_ENV['MONGODB_URL'] = 'mongodb://localhost:27017/test';
        // environment variables not set
        unset($_ENV['LOCALSTACK_ENDPOINT']);
        unset($_ENV['SQS_QUEUE_URL']);
        unset($_ENV['AWS_ACCESS_KEY_ID']);
        unset($_ENV['SES_FROM_EMAIL']);

        $input = new ArrayInput([]);
        $output = new BufferedOutput();

        $exitCode = $this->command->run($input, $output);
        $outputContent = $output->fetch();

        $this->assertEquals(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Message Processor Health Check', $outputContent);
        $this->assertStringContainsString('✅ APP_ENV: ***', $outputContent);
        $this->assertStringContainsString('✅ MONGODB_URL: ***', $outputContent);
        $this->assertStringContainsString('❌ LOCALSTACK_ENDPOINT: NOT SET', $outputContent);
        $this->assertStringContainsString('❌ SQS_QUEUE_URL: NOT SET', $outputContent);
        $this->assertStringContainsString('❌ AWS_ACCESS_KEY_ID: NOT SET', $outputContent);
        $this->assertStringContainsString('❌ SES_FROM_EMAIL: NOT SET', $outputContent);
    }

    public function testCommandShowsMessengerConsumeNote(): void
    {
        $input = new ArrayInput([]);
        $output = new BufferedOutput();

        $this->command->run($input, $output);
        $outputContent = $output->fetch();

        $this->assertStringContainsString('messenger:consume sqs', $outputContent);
    }

    protected function tearDown(): void
    {
        $envVars = [
            'APP_ENV',
            'MONGODB_URL',
            'LOCALSTACK_ENDPOINT',
            'SQS_QUEUE_URL',
            'AWS_ACCESS_KEY_ID',
            'SES_FROM_EMAIL'
        ];

        foreach ($envVars as $var) {
            unset($_ENV[$var]);
        }
    }
}
