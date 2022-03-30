<?php

namespace unit\Cloud\Console\Command;

use Kiboko\Component\Satellite\Cloud\Console\Command\CreateCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class CreateCommandTest extends TestCase
{
    public function testCreateCommandWithUrlAndDisablingSSL(): void
    {
        $command = new CreateCommand();
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'config' => __DIR__.'/../../../examples/satellite.yaml',
            '--url' => 'https://localhost',
            '--ssl' => '--no-ssl'
        ]);

        $commandTester->assertCommandIsSuccessful();
    }
}
