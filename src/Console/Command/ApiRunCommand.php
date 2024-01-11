<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Console\Command;

use Symfony\Component\Console;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[Console\Attribute\AsCommand('run:api', 'Run an HTTP API satellite.', hidden: true)]
final class ApiRunCommand extends RunCommand
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

        $style->warning([
            'The command "run:hook is deprecated and will be removed in future releases.',
            'Please use the "run" command as a replacement.',
        ]);

        return parent::execute($input, $output);
    }
}
