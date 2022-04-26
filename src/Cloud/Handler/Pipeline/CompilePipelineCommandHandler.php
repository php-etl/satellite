<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Handler\Pipeline;

use Gyroscops\Api;
use Kiboko\Component\Satellite\Cloud;

final class CompilePipelineCommandHandler
{
    public function __construct(
        private Api\Client $client
    ) {}

    public function __invoke(Cloud\Command\Pipeline\CompilePipelineCommand $command): Cloud\Event\CompiledPipeline
    {
        try {
            /** @var \stdClass $result */
            $result = $this->client->pipelineCompilationPipelineCollection(
                (new Api\Model\PipelineCompilePipelineCommandInput())->setPipeline((string) $command->pipeline),
            );
        } catch (Api\Exception\PipelineCompilationPipelineCollectionBadRequestException $exception) {
            throw new Cloud\CompilePipelineFailedException(
                'Something went wrong while trying to compile the pipeline. Maybe your client is not up to date, you may want to update your Gyroscops client.',
                previous: $exception
            );
        } catch (Api\Exception\PipelineCompilationPipelineCollectionUnprocessableEntityException $exception) {
            throw new Cloud\CompilePipelineFailedException(
                'Something went wrong while trying to compile the pipeline. It seems the data you sent was invalid, please check your input.',
                previous: $exception
            );
        }

        if ($result === null) {
            // TODO: change the exception message, it doesn't give enough details on how to fix the issue
            throw new Cloud\CompilePipelineFailedException('Something went wrong while trying to compile the pipeline.');
        }

        return new Cloud\Event\CompiledPipeline($result->id);
    }
}
