<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Console\Command;

use Gyroscops\Api;
use Kiboko\Component\Satellite;
use Symfony\Component\Config;
use Symfony\Component\Config\Exception\LoaderLoadException;
use Symfony\Component\Console;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;

final class CreateCommand extends Console\Command\Command
{
    protected static $defaultName = 'create';
    protected static $defaultDescription = 'Sends configuration to the Gyroscops API.';

    protected function configure(): void
    {
        $this->addOption('url', 'u', mode: Console\Input\InputArgument::OPTIONAL, description: 'Base URL of the cloud instance', default: 'https://app.gyroscops.com');
        $this->addOption('beta', mode: Console\Input\InputOption::VALUE_NONE, description: 'Shortcut to set the cloud instance to https://beta.gyroscops.com');
        $this->addOption('ssl', mode: Console\Input\InputOption::VALUE_NEGATABLE, description: 'Enable or disable SSL');
        $this->addArgument('config', Console\Input\InputArgument::REQUIRED);
        $this->addArgument('type', Console\Input\InputArgument::REQUIRED, 'Type de configuration (pipeline ou workflow)');
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
        } elseif ($input->getOption('url')) {
            $url = $input->getOption('url');
            $ssl = $input->getOption('ssl') ?? true;
        } else {
            $url = 'https://gyroscops.com';
            $ssl = $input->getOption('ssl') ?? true;
        }

        $filename = $input->getArgument('config');
        if (null !== $filename) {
            $configuration = (new Satellite\ConfigLoader(getcwd()))->loadFile($filename);
        } else {
            $possibleFiles = ['satellite.yaml', 'satellite.yml', 'satellite.json'];

            foreach ($possibleFiles as $filename) {
                try {
                    $configuration = (new Satellite\ConfigLoader(getcwd()))->loadFile($filename);
                    break;
                } catch (LoaderLoadException) {
                }
            }

            if (!isset($configuration)) {
                throw new \RuntimeException('Could not find configuration file.');
            }
        }

        $type = $input->getArgument('type');
        if ($type !== 'pipeline' && $type !== 'workflow') {
            $output->writeln('Le type doit être soit "pipeline" soit "workflow".');
            return Console\Command\Command::FAILURE;
        }

        for ($directory = getcwd(); '/' !== $directory; $directory = \dirname($directory)) {
            if (file_exists($directory.'/.gyro.php')) {
                break;
            }
        }

        if (!file_exists($directory.'/.gyro.php')) {
            throw new \RuntimeException('Could not load Gyroscops Satellite plugins.');
        }

        $context = new Satellite\Console\RuntimeContext(
            'php://fd/3',
            new Satellite\ExpressionLanguage\ExpressionLanguage(),
        );

        $factory = require $directory.'/.gyro.php';
        $service = $factory($context);

        try {
            $configuration = $service->normalize($configuration);
        } catch (Config\Definition\Exception\InvalidConfigurationException|Config\Definition\Exception\InvalidTypeException $exception) {
            $style->error($exception->getMessage());

            return self::FAILURE;
        }

        $auth = new Satellite\Cloud\Auth();

        try {
            $token = $auth->token($url);
        } catch (Satellite\Cloud\AccessDeniedException) {
            $style->error('Your credentials were not found or has expired.');
            $style->writeLn('You may want to run <info>cloud login</>.');

            return self::FAILURE;
        }

        $httpClient = HttpClient::createForBaseUri(
            $url,
            [
                'verify_peer' => $ssl,
                'auth_bearer' => $token,
            ]
        );

        $psr18Client = new Psr18Client($httpClient);
        $client = Api\Client::create(httpClient: $psr18Client, additionalNormalizers: [
            new Satellite\Cloud\Normalizer\ExpressionNormalizer(),
        ]);

        $bus = Satellite\Cloud\CommandBus::withStandardHandlers($client);

        $configPath = $input->getArgument('config');
        $configDirectory = \dirname((string) $configPath);
        if (file_exists($configDirectory.'/satellite.lock')) {
            throw new \RuntimeException('Pipeline cannot be created, a lock file is present.');
        }

        $context = new Satellite\Cloud\Context($client, $auth, $url);

        if ($type === 'pipeline') {
            $pipeline = new Satellite\Cloud\Pipeline($context);
            if (!\array_key_exists('version', $configuration)) {
                foreach ($pipeline->create(Satellite\Cloud\Pipeline::fromLegacyConfiguration($configuration['satellite'])) as $command) {
                    $bus->push($command);
                }
            } else {
                foreach ($configuration['satellites'] as $satellite) {
                    foreach ($pipeline->create(Satellite\Cloud\Pipeline::fromLegacyConfiguration($satellite)) as $command) {
                        $bus->push($command);
                    }
                }
            }
        } elseif ($type === 'workflow') {
            $workflow = new Satellite\Cloud\Workflow($context);
            if (!\array_key_exists('version', $configuration)) {
                foreach ($workflow->create(Satellite\Cloud\Workflow::fromLegacyConfiguration($configuration['satellite'])) as $command) {
                    $bus->push($command);
                }
            } else {
                foreach ($configuration['satellites'] as $satellite) {
                    foreach ($workflow->create(Satellite\Cloud\Workflow::fromLegacyConfiguration($satellite)) as $command) {
                        $bus->push($command);
                    }
                }
            }
        }

        $bus->execute();

        $style->success('The satellite configuration has been pushed successfully.');

        return Console\Command\Command::SUCCESS;
    }
}
