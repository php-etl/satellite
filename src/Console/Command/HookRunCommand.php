<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Console\Command;

use Composer\Autoload\ClassLoader;
use Symfony\Component\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class HookRunCommand extends Console\Command\Command
{
    protected static $defaultName = 'run:hook';

    protected function configure()
    {
        $this->setDescription('Run the hook.');
        $this->addArgument('config', Console\Input\InputArgument::REQUIRED);
        $this->addArgument('path', Console\Input\InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $style = new Console\Style\SymfonyStyle(
            $input,
            $output,
        );

        $style->writeln(sprintf('<fg=cyan>Running in %s</>', $input->getArgument('path')));

        $command = $this->getApplication()->find('build');
        $arguments = [
            'config' => $input->getArgument('config')
        ];

        $commandInput = new Console\Input\ArrayInput($arguments);
        $command->run($commandInput, $output);

        /** @var ClassLoader $autoload */
        if (!file_exists($input->getArgument('path') . '/vendor/autoload.php')) {
            $style->error('Nothing is compiled at the provided path');
            return 1;
        }

        $cwd = getcwd();
        chdir($input->getArgument('path'));

        $style->writeln(PHP_EOL.'<fg=cyan>Server</>');
        $process = new Process(['php', '-S', 'localhost:8000', 'main.php']);
        $process->setTimeout(null);
        $process->run(function ($type, $buffer) {
            echo $buffer;
        });

        chdir($cwd);

        return 0;
    }
}
