<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Console\Command\Cloud;

use Nyholm\Psr7\Request;
use Symfony\Component\Console;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;

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

        $client = new Psr18Client(HttpClient::create([
            // Need this options with localhost, should be removed
            'verify_peer' => false,
            'verify_host' => true,
        ]));

        $request = new Request(
            'POST',
            $input->getArgument('url'),
            [
                'Content-Type' => 'application/json'
            ],
            json_encode([
                'username' => $input->getArgument('username'),
                'password' => $input->getArgument('password')
            ], JSON_THROW_ON_ERROR)
        );
        $response = $client->sendRequest($request);

        if ($response->getStatusCode() === 200) {
            $concurrentDirectory = getcwd() . '/.gyroscops';
            if (!file_exists($concurrentDirectory) && !mkdir($concurrentDirectory) && !is_dir($concurrentDirectory)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
            }
            file_put_contents($concurrentDirectory . '/auth.json', $response->getBody()->getContents(), JSON_THROW_ON_ERROR);
        } else {
            $style->error('Unable to retrieve the token.');
            return Console\Command\Command::FAILURE;
        }

        $style->success('Authentication token successfully recovered.');

        return Console\Command\Command::SUCCESS;
    }
}
