<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Handler\Pipeline;

use Gyroscops\Api;
use Kiboko\Component\Satellite\Cloud;

final class RemovePipelineCommandHandler
{
    public function __construct(
        private Api\Client $client
    ) {}

    public function __invoke(Cloud\Command\Pipeline\RemovePipelineCommand $command): Cloud\Event\RemovedPipeline
    {
        $result = $this->client->deletePipelinePipelineItem((string) $command->pipeline);

        if ($result === null) {
            throw new Cloud\RemovePipelineConfigurationException('Something went wrong while trying to remove a step from the pipeline.');
        }

        return new Cloud\Event\RemovedPipeline((string) $command->pipeline);
    }
}
