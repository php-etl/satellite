<?php

namespace unit\Cloud\Console\Command;

use Kiboko\Component\Satellite\Cloud;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class CreateCommandTest extends TestCase
{
    public function testExecute(): void
    {
        $command = new  Cloud\Console\Command\CreateCommand();
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'config' => __DIR__.'/../../../examples/satellite.yaml',
            '--url' => 'https://localhost',
            '--ssl' => '--no-ssl'
        ]);

        $commandTester->assertCommandIsSuccessful();

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('The satellite configuration has been pushed successfully.', $output);
    }
}
