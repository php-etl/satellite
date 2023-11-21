<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Console\Command;

use React\ChildProcess\Process;
use React\Promise\Deferred;
use React\Stream\ReadableResourceStream;
use Symfony\Component\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function React\Async\await;

#[Console\Attribute\AsCommand('run', 'Run a data flow satellite (pipeline or workflow).')]
class RunCommand extends Console\Command\Command
{
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

        if (!file_exists($input->getArgument('path').'/vendor/autoload.php')) {
            $style->error('There is no compiled satellite at the provided path.');

            return Console\Command\Command::FAILURE;
        }

        $cwd = getcwd();
        chdir($input->getArgument('path'));

        if (file_exists('pipeline.php')) {
            $style->writeln(sprintf('<fg=cyan>Running pipeline in %s</>', $input->getArgument('path')));

            $process = $this->dataflowWorker($style, $cwd, $input->getArgument('path'), 'pipeline.php');
        } else if (file_exists('workflow.php')) {
            $style->writeln(sprintf('<fg=cyan>Running workflow in %s</>', $input->getArgument('path')));

            $process = $this->dataflowWorker($style, $cwd, $input->getArgument('path'), 'workflow.php');
        } else if (file_exists('main.php')) {
            $style->writeln(sprintf('<fg=cyan>Running API in %s</>', $input->getArgument('path')));

            $process = $this->httpWorker($style, $cwd, $input->getArgument('path'), 'main.php');
        } else {
            $style->error('The provided path does not contain either a workflow or a pipeline satellite, did you mean to run "run:api"?');
            return Console\Command\Command::FAILURE;
        }

        $start = microtime(true);

        if (!$this->executeWorker($style, $process)) {
            return Console\Command\Command::FAILURE;
        }

        $end = microtime(true);

        $style->writeln(sprintf('time: %s', $this->formatTime($end - $start)));

        return Console\Command\Command::SUCCESS;
    }

    private function dataflowWorker(Console\Style\SymfonyStyle $style, string $cwd, string $path, string $entrypoint): Process
    {
        $source =<<<PHP
        \$dotenv = new Dotenv();
        \$dotenv->usePutenv();

        if (file_exists(\$file = $cwd.'/.env')) {
            \$dotenv->loadEnv(\$file);
        }
        if (file_exists(\$file = $cwd.'/'.{$path}.'/.env')) {
            \$dotenv->loadEnv(\$file);
        }

        /** @var ClassLoader \$autoload */
        \$autoload = include 'vendor/autoload.php';
        \$autoload->addClassMap([
            /* @phpstan-ignore-next-line */
            \ProjectServiceContainer::class => 'container.php',
        ]);
        \$autoload->register();

        \$runtime = new PipelineConsoleRuntime(
            new Symfony\Component\Console\Output\StreamOutput(STDOUT),
            new \Kiboko\Component\Pipeline\Pipeline(
                new \Kiboko\Component\Pipeline\PipelineRunner(
                    new \Psr\Log\NullLogger()
                )
            ),
        );
        
        \$satellite = include '$entrypoint';
        
        \$satellite(\$runtime);
        \$runtime->run();
        
        \$autoload->unregister();
        PHP;

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $source);
        fseek($stream, 0, SEEK_SET);

        $input = new ReadableResourceStream($stream);

        chdir($cwd);

        $command = ['php', '-r', '--'];

        $process = new Process(implode (' ', array_map(fn ($part) => escapeshellarg($part), $command)), $cwd);
        $process->start();

        $input->pipe($process->stdin);

        return $process;
    }


    private function httpWorker(Console\Style\SymfonyStyle $style, string $cwd, string $path, string $entrypoint): Process
    {
        chdir($cwd);

        $command = ['php', '-S', 'localhost:8000', 'main.php'];

        $process = new Process(implode (' ', array_map(fn ($part) => escapeshellarg($part), $command)), $cwd);

        return $process;
    }

    private function formatTime(float $time): string
    {
        if ($time < .00001) {
            return sprintf('<fg=cyan>%sµs</>', number_format($time * 1_000_000, 2));
        }
        if ($time < .0001) {
            return sprintf('<fg=cyan>%sµs</>', number_format($time * 1_000_000, 1));
        }
        if ($time < .001) {
            return sprintf('<fg=cyan>%sµs</>', number_format($time * 1_000_000));
        }
        if ($time < .01) {
            return sprintf('<fg=cyan>%sms</>', number_format($time * 1000, 2));
        }
        if ($time < .1) {
            return sprintf('<fg=cyan>%sms</>', number_format($time * 1000, 1));
        }
        if ($time < 1) {
            return sprintf('<fg=cyan>%sms</>', number_format($time * 1000));
        }
        if ($time < 10) {
            return sprintf('<fg=cyan>%ss</>', number_format($time, 2));
        }
        if ($time < 3600) {
            $minutes = floor($time / 60);
            $seconds = $time - (60 * $minutes);

            return sprintf('<fg=cyan>%smin</> <fg=cyan>%ss</>', number_format($minutes), number_format($seconds, 2));
        }
        $hours = floor($time / 3600);
        $minutes = floor(($time - (3600 * $hours)) / 60);
        $seconds = $time - (3600 * $hours) - (60 * $minutes);

        return sprintf('<fg=cyan>%sh</> <fg=cyan>%smin</> <fg=cyan>%ss</>', number_format($hours), number_format($minutes), number_format($seconds, 2));
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
