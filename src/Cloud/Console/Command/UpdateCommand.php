<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Console\Command;

use Gyroscops\Api;
use Gyroscops\Api\Model\PipelineStep;
use Kiboko\Component\Satellite;
use Symfony\Component\Config;
use Symfony\Component\Config\Exception\LoaderLoadException;
use Symfony\Component\Console;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;

final class UpdateCommand extends Console\Command\Command
{
    protected static $defaultName = 'update';

    protected function configure(): void
    {
        $this->setDescription('Updates the configuration to the Gyroscops API.');
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

        $lockFile = dirname(getcwd() . '/' . $input->getArgument('config')) . '/satellite.lock';
        if (!file_exists($lockFile)) {
            throw new \RuntimeException('Pipeline should be created before updated it.');
        }

        $pipelineId = json_decode(file_get_contents($lockFile), true, 512, JSON_THROW_ON_ERROR)["id"];
        $response = $client->getPipelineItem($pipelineId, Client::FETCH_RESPONSE);
        if ($response !== null && $response->getStatusCode() !== 200 ) {
            throw new \RuntimeException($response->getReasonPhrase());
        }

        $steps = $client->apiPipelinesStepsGetSubresourcePipelineSubresource($pipelineId);
        $iterator = new \MultipleIterator(\MultipleIterator::MIT_NEED_ANY);
        $iterator->attachIterator(new \ArrayIterator($steps));
        $iterator->attachIterator(new \ArrayIterator($configuration["satellite"]["pipeline"]["steps"]));

        /**
         * @var PipelineStep $result
         */
        foreach ($iterator as [$result, $step]) {
            if (!is_null($result) && !is_null($step) && ($result->getCode() !== $step["code"])) {
                $bus->execute(
                    new Satellite\Cloud\Command\Pipeline\ReplacePipelineStepCommand(
                        $pipelineId,
                        $result->getCode(),
                        $step["code"],
                        $step["name"],
                        $step,
                        []
                    )
                );
            }

            if (!is_null($step)) {
                $bus->execute(
                    new Satellite\Cloud\Command\Pipeline\AppendPipelineStepCommand(
                        $pipelineId,
                        $step["code"],
                        $step["name"],
                        $step,
                        []
                    )
                );
            }

            if (is_null($step)) {
                $bus->execute(
                    new Satellite\Cloud\Command\Pipeline\RemovePipelineStepCommand(
                        $pipelineId,
                        $result->getCode(),
                    )
                );
            }
        }

        $style->success('The satellite configuration has been updated correctly.');

        return Console\Command\Command::SUCCESS;
    }
}
