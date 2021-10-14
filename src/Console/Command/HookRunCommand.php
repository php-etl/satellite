<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Console\Command;

use Composer\Autoload\ClassLoader;
use Symfony\Component\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

final class HookRunCommand extends Console\Command\Command
{
    protected static $defaultName = 'run:hook';

    protected function configure()
    {
        $this->setDescription('Run the hook.');
        $this->addArgument('path', Console\Input\InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $style = new Console\Style\SymfonyStyle(
            $input,
            $output,
        );

        $style->writeln(sprintf('<fg=cyan>Running hook in %s</>', $input->getArgument('path')));

        /** @var ClassLoader $autoload */
        if (!file_exists($input->getArgument('path') . '/vendor/autoload.php')) {
            $style->error('There is no compiled hook at the provided path');
            return 1;
        }

        $cwd = getcwd();
        chdir($input->getArgument('path'));

        $process = new Process(['php', '-S', 'localhost:8000', 'main.php']);
        $process->setTimeout(null);
        $process->run();

//        $autoload = include 'vendor/autoload.php';
//        $autoload->register();
//
//        require 'container.php';
//        $container = new \ProjectServiceContainer();
//
//        $runtime = new Satellite\Console\HookConsoleRuntime(
//            $output,
//            new \Kiboko\Component\Satellite\Component\Hook(),
//            new \Kiboko\Component\Pipeline\PipelineRunner(new \Psr\Log\NullLogger()),
//            $container
//        );
//
//        /** @var callable(runtime: HookRuntimeInterface): \Runtime $hook */
//        $hook = include 'hook.php';
//
//        $start = microtime(true);
//        $start = microtime(true);
//        $hook($runtime);
//        $runtime->run();
//        $end = microtime(true);
//
//        $autoload->unregister();
//
//        $style->writeln(sprintf('time: %s', $this->formatTime($end - $start)));

        chdir($cwd);

        return 0;
    }

    private function formatTime(float $time): string
    {
        if ($time < .00001) {
            return sprintf('<fg=cyan>%sµs</>', number_format($time * 1000000, 2));
        }
        if ($time < .0001) {
            return sprintf('<fg=cyan>%sµs</>', number_format($time * 1000000, 1));
        }
        if ($time < .001) {
            return sprintf('<fg=cyan>%sµs</>', number_format($time * 1000000));
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
