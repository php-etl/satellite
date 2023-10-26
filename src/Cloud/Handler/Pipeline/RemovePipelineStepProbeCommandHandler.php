<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Handler\Pipeline;

use Gyroscops\Api;
use Kiboko\Component\Satellite\Cloud;

final readonly class RemovePipelineStepProbeCommandHandler
{
    public function __construct(
        private Api\Client $client
    ) {}

    public function __invoke(Cloud\Command\Pipeline\RemovePipelineStepProbeCommand $command): Cloud\Event\RemovedPipelineStepProbe
    {
        try {
            /** @var \stdClass $result */
            $result = $this->client->removePipelineStepProbePipelineItem(
                (string) $command->stepCode,
                $command->probe->code,
                $command->probe->label,
                (string) $command->pipeline,
            );
        } catch (Api\Exception\RemovePipelineStepProbePipelineItemNotFoundException $exception) {
            throw new Cloud\RemovePipelineStepProbeFailedException('Something went wrong while removing a probe from the step. Maybe you are trying to delete a probe that never existed or has already been deleted.', previous: $exception);
        }

        return new Cloud\Event\RemovedPipelineStepProbe($result->id);
    }
}
