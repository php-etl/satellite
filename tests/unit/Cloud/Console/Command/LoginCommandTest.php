<?php

namespace unit\Cloud\Console\Command;

use Kiboko\Component\Satellite\Cloud\Console\Command\LoginCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class LoginCommandTest extends TestCase
{
    public function testLoginCommandWithUrlAndDisablingSSL(): void
    {
        $command = new LoginCommand();
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'username' => 'admin',
            '--url' => 'https://localhost',
            '--ssl' => '--no-ssl'
        ]);

        $commandTester->assertCommandIsSuccessful();
    }
}
