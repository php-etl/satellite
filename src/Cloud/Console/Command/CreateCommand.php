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

    protected function configure(): void
    {
        $this->setDescription('Sends configuration to the Gyroscops API.');
        $this->addOption('url', 'u', mode: Console\Input\InputArgument::OPTIONAL, description: 'Base URL of the cloud instance', default: 'https://app.gyroscops.com');
        $this->addOption('beta', mode: Console\Input\InputOption::VALUE_NONE, description: 'Shortcut to set the cloud instance to https://beta.gyroscops.com');
        $this->addOption('ssl', mode: Console\Input\InputOption::VALUE_NEGATABLE, description: 'Enable or disable SSL');
        $this->addArgument('config', Console\Input\InputArgument::REQUIRED);
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

        $filename = $input->getArgument('config');
        if ($filename !== null) {
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

        $service = new Satellite\Cloud\Service();

        try {
            $configuration = $service->normalize($configuration);
        } catch (Config\Definition\Exception\InvalidTypeException | Config\Definition\Exception\InvalidConfigurationException $exception) {
            $style->error($exception->getMessage());
            return self::FAILURE;
        }

        $auth = new Satellite\Cloud\Auth();
        try {
            $token = $auth->token($url);
        } catch (\OutOfBoundsException) {
            $style->error(sprintf('Your credentials were not found, please run <info>%s login</>.', $input->getFirstArgument()));
            return self::FAILURE;
        }

        $httpClient = HttpClient::createForBaseUri(
            $url,
            [
                'verify_peer' => $ssl,
                'auth_bearer' => $token
            ]
        );

        $psr18Client = new Psr18Client($httpClient);
        $client = Api\Client::create($psr18Client);

        $bus = Satellite\Cloud\CommandBus::withStandardHandlers($client);

        $configPath = $input->getArgument('config');
        $configDirectory = dirname($configPath);
        if (file_exists($configDirectory . '/satellite.lock')) {
            throw new \RuntimeException('Pipeline cannot be created, a lock file is present.');
        }

        $bus->push(
            new Satellite\Cloud\Command\Pipeline\DeclarePipelineCommand(
                $configuration['satellite']['pipeline']['name'],
                $configuration['satellite']['pipeline']['code'],
                new Satellite\Cloud\DTO\ProjectId($configuration['satellite']['cloud']['project']),
                new Satellite\Cloud\DTO\StepList(
                    ...array_map(function (array $stepConfig) {
                        $name = $stepConfig['name'];
                        $code = $stepConfig['code'];
                        unset($stepConfig['name'], $stepConfig['code']);

                        return new Satellite\Cloud\DTO\Step(
                            $name,
                            $code,
                            $stepConfig,
                        );
                    }, $configuration['satellite']['pipeline']['steps'])
                ),
                new Satellite\Cloud\DTO\Autoload(
                    ...array_map(
                        function (
                            string $namespace,
                            array $paths,
                        ): Satellite\Cloud\DTO\PSR4AutoloadConfig {
                            return new Satellite\Cloud\DTO\PSR4AutoloadConfig($namespace, ...$paths['paths']);
                        },
                        array_keys($configuration['satellite']['composer']['autoload']['psr4'] ?? []),
                        $configuration['satellite']['composer']['autoload']['psr4'] ?? [],
                    )
                )
            )
        )->then(
            function (Satellite\Cloud\DTO\PipelineId $pipeline) use ($configDirectory) {
                file_put_contents(
                    $configDirectory . '/.lock',
                    json_encode(['id' => (string) $pipeline], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT),
                );
            },
            function (\Throwable $exception) use ($style) {
                $style->error($exception->getMessage());
            }
        );

        $bus->execute();

        $style->success('The satellite configuration has been sent correctly.');

        return Console\Command\Command::SUCCESS;
    }
}
