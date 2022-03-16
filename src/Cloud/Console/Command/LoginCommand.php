<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Console\Command;

use Gyroscops\Api;
use Kiboko\Component\Satellite;
use Kiboko\Component\Satellite\Cloud\AccessDeniedException;
use Symfony\Component\Console;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;

final class LoginCommand extends Console\Command\Command
{
    protected static $defaultName = 'login';

    protected function configure(): void
    {
        $this->setDescription('Authenticate to the Gyroscops API.');
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
        $auth = new Satellite\Cloud\Auth();

        try {
            $credentials = $auth->credentials($url);

            $token = $auth->authenticateWithCredentials($client, $credentials);
            $auth->persistToken($url, $token);
            $auth->flush();

            $style->success('Authentication token successfully refreshed.');

            return self::SUCCESS;
        } catch (\AssertionError) {
            // NOOP: The provided credentials are incorrect
        } catch (\OutOfBoundsException) {
            // NOOP: No credentials found for the provided instance
        }

        $retries = 3;
        $username = $input->getArgument('username') ?? $style->ask('Username:');

        while (true) {
            try {
                $password = $style->askHidden('Password:');
                $token = $auth->authenticateWithCredentials($client, new Satellite\Cloud\Credentials($username, $password));

                $auth->persistCredentials($url, new Satellite\Cloud\Credentials($username, $password));
                $auth->persistToken($url, $token);
                $auth->flush();

                $style->success('Authentication token successfully stored.');
                break;
            } catch (AccessDeniedException) {
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
