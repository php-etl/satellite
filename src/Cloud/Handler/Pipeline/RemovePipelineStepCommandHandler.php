<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Handler\Pipeline;

use Gyroscops\Api;
use Kiboko\Component\Satellite\Cloud;

final class RemovePipelineStepCommandHandler
{
    public function __construct(
        private Api\Client $client
    ) {}

    public function __invoke(Cloud\Command\Pipeline\RemovePipelineStepCommand $command): Cloud\Event\RemovedPipelineStep
    {
        $result = $this->client->deletePipelineStepPipelineItem(
            (string) $command->code,
            (string) $command->pipeline,
        );

        if ($result === null) {
            throw throw new \RuntimeException('Something went wrong wile removing a step from the pipeline.');
        }

        return new Cloud\Event\RemovedPipelineStep((string) $command->code);
    }
}
