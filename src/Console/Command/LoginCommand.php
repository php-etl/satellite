<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Console\Command;

use GuzzleHttp\Client;
use Symfony\Component\Console;

final class LoginCommand extends Console\Command\Command
{
    protected static $defaultName = 'login';

    protected function configure(): void
    {
        $this->setDescription('Connects to the Gyroscops API.');
        $this->addArgument('url', Console\Input\InputArgument::REQUIRED);
        $this->addArgument('username', Console\Input\InputArgument::REQUIRED);
        $this->addArgument('password', Console\Input\InputArgument::REQUIRED);
    }

    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output): int
    {
        $style = new Console\Style\SymfonyStyle(
            $input,
            $output,
        );

        $client = new Client();
        $response = $client->request(
            'POST',
            $input->getArgument('url'),
            [
                'body' => [
                    'username' => $input->getArgument('username'),
                    'password' => $input->getArgument('password')
                ]
            ]
        );

        $response = $response->getBody();

        return Console\Command\Command::SUCCESS;
    }
}
