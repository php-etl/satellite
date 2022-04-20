<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Handler\Pipeline;

use Gyroscops\Api;
use Kiboko\Component\Satellite\Cloud;

final class AddPipelineComposerPSR4AutoloadCommandHandler
{
    public function __construct(
        private Api\Client $client
    ) {}

    public function __invoke(Cloud\Command\Pipeline\AddPipelineComposerPSR4AutoloadCommand $command): Cloud\Event\AddedPipelineComposerPSR4Autoload
    {
        $result = $this->client->addComposerPipelinePipelineCollection(
            (new Api\Model\PipelineAddPipelineComposerPSR4AutoloadCommandInput())
                ->setPipeline((string) $command->pipeline)
                ->setNamespace($command->namespace)
                ->setPaths($command->paths),
        );

        if ($result === null) {
            throw new Cloud\SendPipelineConfigurationException('Something went wrong while trying to add PSR4 autoloads into the pipeline.');
        }

        return new Cloud\Event\AddedPipelineComposerPSR4Autoload($result->id, $result->namespace, $result->paths);
    }
}
