<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Console\Command\Cloud;

use Gyroscops\Api\Client;
use Kiboko\Component\Satellite;
use Symfony\Component\Config\Exception\LoaderLoadException;
use Symfony\Component\Config;
use Symfony\Component\Console;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;

final class CreateCommand extends Console\Command\Command
{
    protected static $defaultName = 'create';

    protected function configure(): void
    {
        $this->setDescription('Sends configuration to the Gyroscops API.');
        $this->addArgument('config', Console\Input\InputArgument::REQUIRED);
        $this->addOption('disable-ssl', null, Console\Input\InputOption::VALUE_OPTIONAL,
            '',
            false
        );
    }

    protected function execute(Console\Input\InputInterface $input, Console\Output\OutputInterface $output): int
    {
        $style = new Console\Style\SymfonyStyle(
            $input,
            $output,
        );

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
            return 255;
        }

        $token = json_decode(file_get_contents(getcwd() . '/.gyroscops/auth.json'), true, 512, JSON_THROW_ON_ERROR)["token"];
        if (!$token) {
            throw new TokenException('Unable to retrieve authentication token.');
        }

        $httpClient = HttpClient::createForBaseUri(
            $configuration["satellite"]["cloud"]["url"],
            [
                'verify_peer' => $input->getOption('disable-ssl') === "true" ? false : true,
                'auth_bearer' => $token
            ]
        );

        $psr18Client = new Psr18Client($httpClient);
        $client = Client::create($psr18Client);

        $bus = new Satellite\Cloud\CommandBus([
            Satellite\Cloud\Command\Pipeline\DeclarePipelineCommand::class => new Satellite\Cloud\Handler\Pipeline\DeclarePipelineCommandHandler($client),
            Satellite\Cloud\Command\Pipeline\AddPipelineComposerPSR4AutoloadCommand::class => new Satellite\Cloud\Handler\Pipeline\AddPipelineComposerPSR4AutoloadCommandHandler($client),
            Satellite\Cloud\Command\Pipeline\AppendPipelineStepCommand::class => new Satellite\Cloud\Handler\Pipeline\AppendPipelineStepCommandHandler($client),
        ]);

        $currentDirectory = dirname(getcwd() . '/' . $input->getArgument('config'));
        if (file_exists($currentDirectory . '/satellite.lock')) {
            throw new \RuntimeException('Pipeline cannot be created because its already exists.');
        }

        $result = $bus->execute(
            new Satellite\Cloud\Command\Pipeline\DeclarePipelineCommand(
                $configuration["satellite"]["pipeline"]["name"],
                $configuration["satellite"]["pipeline"]["code"],
                $configuration["satellite"]["cloud"]["project"]
            )
        );

        $currentDirectory = dirname(getcwd() . '/' . $input->getArgument('config'));
        if (!file_exists($currentDirectory) && !mkdir($currentDirectory) && !is_dir($currentDirectory)) {
            throw new \RuntimeException(sprintf('Directory "%s" was not created', $currentDirectory));
        }
        file_put_contents($currentDirectory . '/satellite.lock',
            json_encode(['id' => $result->getId()], JSON_THROW_ON_ERROR),
            JSON_THROW_ON_ERROR
        );

        if (array_key_exists('composer', $configuration["satellite"])
            && array_key_exists('autoload', $configuration["satellite"]["composer"])
            && array_key_exists('psr4', $configuration["satellite"]["composer"]["autoload"])
        ) {
            foreach ($configuration["satellite"]["composer"]["autoload"]["psr4"] as $key => $autoload) {
                $bus->execute(
                    new Satellite\Cloud\Command\Pipeline\AddPipelineComposerPSR4AutoloadCommand(
                        $result->getId(),
                        $key,
                        $autoload["paths"]
                    )
                );
            }
        }

        foreach ($configuration["satellite"]["pipeline"]["steps"] as $step) {
            $bus->execute(
                new Satellite\Cloud\Command\Pipeline\AppendPipelineStepCommand(
                    $result->getId(),
                    $step["code"],
                    $step["name"],
                    array_splice($step, -1),
                    []
                )
            );
        }

        $style->success('The satellite configuration has been sent correctly.');

        return Console\Command\Command::SUCCESS;
    }
}
