<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Console\Command\Cloud;

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

        $client = \Gyroscops\Api\Client::create();

        $data = new \Gyroscops\Api\Model\Credentials();
        $data->setUsername($input->getArgument('username'));
        $data->setPassword($input->getArgument('password'));

        $response = $client->postCredentialsItem();

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
