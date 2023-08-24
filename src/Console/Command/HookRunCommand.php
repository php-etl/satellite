<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Console\Command;

use Symfony\Component\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class HookRunCommand extends Console\Command\Command
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

            return \Symfony\Component\Console\Command\Command::FAILURE;
        }

        $cwd = getcwd();
        chdir($input->getArgument('path'));

        $process = new Process(['php', '-S', 'localhost:8000', 'main.php']);
        $process->setTimeout(null);
        $process->run(function ($type, $buffer): void {
            echo $buffer;
        });

        chdir($cwd);

        return \Symfony\Component\Console\Command\Command::SUCCESS;
    }
}
