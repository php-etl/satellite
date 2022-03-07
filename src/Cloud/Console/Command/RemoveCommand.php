<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Console\Command;

use Gyroscops\Api\Client;
use Kiboko\Component\Satellite;
use Symfony\Component\Config;
use Symfony\Component\Config\Exception\LoaderLoadException;
use Symfony\Component\Console;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;

final class RemoveCommand extends Console\Command\Command
{
    protected static $defaultName = 'remove';

    protected function configure(): void
    {
        $this->setDescription('Removes a part of configuration.');
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

        $token = json_decode(file_get_contents(getcwd() . '/.gyroscops/auth.json'), true)["token"];
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
            Satellite\Cloud\Command\Pipeline\RemovePipelineCommand::class => new Satellite\Cloud\Handler\Pipeline\RemovePipelineCommandHandler($client),
        ]);

        $lockFile = dirname(getcwd() . '/' . $input->getArgument('config')) . '/satellite.lock';
        if (!file_exists($lockFile)) {
            throw new \RuntimeException('Pipeline should be created before remove it.');
        }
        $pipelineId = json_decode(file_get_contents($lockFile), true, 512, JSON_THROW_ON_ERROR)["id"];
        $response = $client->getPipelineItem($pipelineId, Client::FETCH_RESPONSE);
        if ($response !== null && $response->getStatusCode() !== 200 ) {
            throw new \RuntimeException($response->getReasonPhrase());
        }

        $bus->execute(
            new Satellite\Cloud\Command\Pipeline\RemovePipelineCommand($pipelineId)
        );

        $style->success('The satellite configuration has been removed correctly.');

        return Console\Command\Command::SUCCESS;
    }
}
