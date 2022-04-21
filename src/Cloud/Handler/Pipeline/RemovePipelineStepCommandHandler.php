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
        try {
            /** @var \stdClass $result */
            $result = $this->client->deletePipelineStepPipelineItem(
                (string) $command->code,
                (string) $command->pipeline,
            );
        } catch (Api\Exception\DeletePipelineStepPipelineItemNotFoundException $exception) {
            throw new Cloud\RemovePipelineStepFailedException(
                'Something went wrong while trying to remove a probe from the step. Maybe you are trying to delete a step that never existed or has already been deleted.',
                previous: $exception
            );
        }

        if ($result === null) {
            // TODO: change the exception message, it doesn't give enough details on how to fix the issue
            throw new Cloud\RemovePipelineStepFailedException('Something went wrong while trying to remove a probe from the step.');
        }

        return new Cloud\Event\RemovedPipelineStep((string) $command->code);
    }
}
