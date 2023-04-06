<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Console\Command;

use Composer\Autoload\ClassLoader;
use Kiboko\Component\Runtime\Pipeline\Console as PipelineConsoleRuntime;
use Symfony\Component\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Dotenv\Dotenv;

final class PipelineRunCommand extends Console\Command\Command
{
    protected static $defaultName = 'run:pipeline';
    protected static $defaultDescription = 'Run the pipeline satellite.';

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

        $style->writeln(sprintf('<fg=cyan>Running pipeline in %s</>', $input->getArgument('path')));

        if (!file_exists($input->getArgument('path').'/vendor/autoload.php')) {
            $style->error('There is no compiled pipeline at the provided path');

            return \Symfony\Component\Console\Command\Command::FAILURE;
        }

        $cwd = getcwd();
        chdir($input->getArgument('path'));

        $dotenv = new Dotenv();
        $dotenv->usePutenv();
        if (file_exists($file = \dirname($cwd).'/.env')) {
            $dotenv->loadEnv($file);
        }

        /** @var ClassLoader $autoload */
        $autoload = include 'vendor/autoload.php';
        $autoload->addClassMap([
            /* @phpstan-ignore-next-line */
            \ProjectServiceContainer::class => 'container.php',
        ]);
        $autoload->register();

        $runtime = new PipelineConsoleRuntime(
            $output,
            new \Kiboko\Component\Pipeline\Pipeline(
                new \Kiboko\Component\Pipeline\PipelineRunner(
                    new \Psr\Log\NullLogger()
                )
            ),
        );

        /** @var callable(runtime: PipelineRuntimeInterface): \Runtime $pipeline */
        $pipeline = include 'pipeline.php';

        $start = microtime(true);
        $pipeline($runtime);
        $runtime->run();
        $end = microtime(true);

        $autoload->unregister();

        $style->writeln(sprintf('time: %s', $this->formatTime($end - $start)));

        chdir($cwd);

        return \Symfony\Component\Console\Command\Command::SUCCESS;
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
}
