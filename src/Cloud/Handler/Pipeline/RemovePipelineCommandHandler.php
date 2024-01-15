<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Handler\Pipeline;

use Gyroscops\Api;
use Kiboko\Component\Satellite\Cloud;

final readonly class RemovePipelineCommandHandler
{
    public function __construct(
        private Api\Client $client
    ) {
    }

    public function __invoke(Cloud\Command\Pipeline\RemovePipelineCommand $command): Cloud\Event\RemovedPipeline
    {
        try {
            /** @var \stdClass $result */
            $result = $this->client->deletePipelinePipelineItem((string) $command->pipeline);
        } catch (Api\Exception\DeletePipelinePipelineItemNotFoundException $exception) {
            throw new Cloud\RemovePipelineFailedException('Something went wrong while trying to remove a step from the pipeline. Maybe you are trying to delete a pipeline that never existed or has already been deleted.', previous: $exception);
        }

        if (null === $result) {
            // TODO: change the exception message, it doesn't give enough details on how to fix the issue
            throw new Cloud\RemovePipelineFailedException('Something went wrong while trying to remove a step from the pipeline.');
        }

        return new Cloud\Event\RemovedPipeline((string) $command->pipeline);
    }
}
