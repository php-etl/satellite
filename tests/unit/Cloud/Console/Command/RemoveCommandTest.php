<?php

namespace unit\Cloud\Console\Command;

use Kiboko\Component\Satellite\Cloud\Console\Command\LoginCommand;
use Kiboko\Component\Satellite\Cloud\Console\Command\RemoveCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class RemoveCommandTest extends TestCase
{
    public function testLoginCommandWithUrlAndDisablingSSL(): void
    {
        $command = new RemoveCommand();
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'config' => __DIR__.'/../../../examples/satellite.yaml',
            '--url' => 'https://localhost',
            '--ssl' => '--no-ssl'
        ]);

        $commandTester->assertCommandIsSuccessful();
    }
}
