<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Console\Command;

use Gyroscops\Api;
use Kiboko\Component\Satellite\Cloud\Auth;
use Kiboko\Component\Satellite\Console\Command\Cloud\Client;
use Symfony\Component\Console;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;

final class LoginCommand extends Console\Command\Command
{
    protected static $defaultName = 'login';

    protected function configure(): void
    {
        $this->setDescription('Connects to the Gyroscops API.');
        $this->addArgument('username', mode: Console\Input\InputArgument::OPTIONAL);
        $this->addOption('url', 'u', mode: Console\Input\InputArgument::OPTIONAL, description: 'Base URL of the cloud instance', default: 'https://app.gyroscops.com');
        $this->addOption('beta', mode: Console\Input\InputOption::VALUE_NONE, description: 'Shortcut to set the cloud instance to https://beta.gyroscops.com');
        $this->addOption('ssl', mode: Console\Input\InputOption::VALUE_NEGATABLE, description: 'Enable or disable SSL');
    }

    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output): int
    {
        $style = new Console\Style\SymfonyStyle(
            $input,
            $output,
        );

        if ($input->getOption('beta')) {
            $url = 'https://beta.gyroscops.com';
            $ssl = $input->getOption('ssl') ?? true;
        } else if ($input->getOption('url')) {
            $url = $input->getOption('url');
            $ssl = $input->getOption('ssl') ?? true;
        } else {
            $url = 'https://gyroscops.com';
            $ssl = $input->getOption('ssl') ?? true;
        }

        $httpClient = HttpClient::createForBaseUri(
            $url,
            [
                'verify_peer' => $ssl,
            ]
        );

        $psr18Client = new Psr18Client($httpClient);
        $client = Api\Client::create($psr18Client);

        $retries = 3;
        $username = $input->getArgument('username') ?? $style->ask('Username:');

        while (true) {
            $data = new \Gyroscops\Api\Model\Credentials();
            $data->setUsername($username);
            $data->setPassword($style->askHidden('Password:'));

            $token = $client->postCredentialsItem($data);
            try {
                assert($token instanceof Api\Model\Token);

                $directory = getenv('HOME') . '/.gyroscops';
                if (!file_exists($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
                    throw new \RuntimeException(sprintf('Directory "%s" can not be created', $directory));
                }

                $auth = new Auth($directory . '/auth.json');
                $auth->append($url, $token->getToken());
                $auth->dump();

                $style->success('Authentication token successfully stored.');
                break;
            } catch (\AssertionError) {
                if (--$retries > 0) {
                    $style->error('Your credentials were not correct, the login was not successful.');
                    continue;
                }

                $style->error('Unable to retrieve the token after 3 retries.');
                return Console\Command\Command::FAILURE;
            }
        }

        return Console\Command\Command::SUCCESS;
    }
}
