<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Console\Command;

use React\ChildProcess\Process;
use React\Promise\Deferred;
use Symfony\Component\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function React\Async\await;

final class HookRunCommand extends Console\Command\Command
{
    protected static $defaultName = 'run:hook';
    protected static $defaultDescription = 'Run the hook.';

    protected function configure(): void
    {
        $this->addArgument('path', Console\Input\InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new Console\Style\SymfonyStyle(
            $input,
            $output,
        );

        $style->writeln(sprintf('<fg=cyan>Starting server in %s</>', $input->getArgument('path')));

        if (!file_exists($input->getArgument('path').'/vendor/autoload.php')) {
            $style->error('Nothing is compiled at the provided path');

            return Console\Command\Command::FAILURE;
        }

        $cwd = getcwd();
        chdir($input->getArgument('path'));

        $command = ['php', '-S', 'localhost:8000', 'main.php'];

        $process = new Process(implode (' ', array_map(fn ($part) => escapeshellarg($part), $command)), $cwd);

        if (!$this->executeWorker($style, $process)) {
            return Console\Command\Command::FAILURE;
        }

        return Console\Command\Command::SUCCESS;
    }

    private function executeWorker(
        Console\Style\SymfonyStyle $style,
        Process $process
    ): bool {
        $process->stdout->on('data', function ($chunk) use ($style) {
            $style->text($chunk);
        });
        $process->stderr->on('data', function ($chunk) use ($style) {
            $style->info($chunk);
        });

        $deferred = new Deferred();

        $process->on('exit', function () use ($deferred) {
            $deferred->resolve();
        });

        $process->start();
        $style->note(sprintf('Starting process "%s".', $process->getCommand()));

        await($deferred->promise());

        if (0 !== $process->getExitCode()) {
            $style->error(sprintf('Process exited unexpectedly with exit code %d', $process->getExitCode()));
            return false;
        }

        return true;
    }
}
