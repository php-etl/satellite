<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Console\Command\Cloud;

use Gyroscops\Api\Client;
use Gyroscops\Api\Model\PipelineStep;
use Kiboko\Component\Satellite;
use Symfony\Component\Config\Exception\LoaderLoadException;
use Symfony\Component\Config;
use Symfony\Component\Console;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Psr18Client;

final class UpdateCommand extends Console\Command\Command
{
    protected static $defaultName = 'update';

    protected function configure(): void
    {
        $this->setDescription('Updates the configuration to the Gyroscops API.');
        $this->addArgument('config', Console\Input\InputArgument::REQUIRED);
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
        if (!$token) {
            throw new TokenException('Unable to retrieve authentication token.');
        }

        $httpClient = HttpClient::createForBaseUri(
            $configuration["satellite"]["cloud"]["url"],
            [
                'verify_peer' => false,
                'auth_bearer' => $token
            ]
        );

        $psr18Client = new Psr18Client($httpClient);
        $client = Client::create($psr18Client);

        $bus = new Satellite\Cloud\CommandBus([
            Satellite\Cloud\Command\Pipeline\AddAfterPipelineStepCommand::class => new Satellite\Cloud\Handler\Pipeline\AddAfterPipelineStepCommandHandler($client),
            Satellite\Cloud\Command\Pipeline\AddBeforePipelineStepCommand::class => new Satellite\Cloud\Handler\Pipeline\AddBeforePipelineStepCommandHandler($client),
            Satellite\Cloud\Command\Pipeline\AddPipelineComposerPSR4AutoloadCommand::class => new Satellite\Cloud\Handler\Pipeline\AddPipelineComposerPSR4AutoloadCommandHandler($client),
            Satellite\Cloud\Command\Pipeline\AppendPipelineStepCommand::class => new Satellite\Cloud\Handler\Pipeline\AppendPipelineStepCommandHandler($client),
            Satellite\Cloud\Command\Pipeline\ReplacePipelineStepCommand::class => new Satellite\Cloud\Handler\Pipeline\ReplacePipelineStepCommandHandler($client),
            Satellite\Cloud\Command\Pipeline\RemovePipelineStepCommand::class => new Satellite\Cloud\Handler\Pipeline\RemovePipelineStepCommandHandler($client),
        ]);

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
            if ($result->getCode() !== $step["code"]) {
                $bus->execute(
                    new Satellite\Cloud\Command\Pipeline\ReplacePipelineStepCommand(
                        $pipelineId,
                        $result->getCode(),
                        $step["code"],
                        $step["label"],
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
                        $step["label"],
                        $step,
                        []
                    )
                );
            }

            if (is_null($step)) {
                $bus->execute(
                    new Satellite\Cloud\Command\Pipeline\RemovePipelineStepCommand(
                        $pipelineId,
                        $step["code"],
                    )
                );
            }
        }

        $style->success('The satellite configuration has been updated correctly.');

        return Console\Command\Command::SUCCESS;
    }
}
