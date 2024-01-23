<?php

declare(strict_types=1);

namespace functional\Cloud\Console\Command;

use Kiboko\Component\Satellite\Cloud\Console\Command\CreateCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class CreateCommandTest extends TestCase
{
    public function testCreateCommandWithOneSatellite(): void
    {
        $application = new Application();
        $application->add(new CreateCommand());

        $command = $application->find('create');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'config' => sys_get_temp_dir(),
            ]
        );

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Résultat attendu', $output);

        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function testCreateCommandWithMultipleSatellite(): void
    {
        $application = new Application();
        $application->add(new CreateCommand());

        $command = $application->find('create');
        $commandTester = new CommandTester($command);
        $commandTester->execute(
            [
                'config' => sys_get_temp_dir(),
            ]
        );

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Résultat attendu', $output);

        $this->assertSame(0, $commandTester->getStatusCode());
    }
}
