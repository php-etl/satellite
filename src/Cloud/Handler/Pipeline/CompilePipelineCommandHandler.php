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
        $result = $this->client->pipelineCompilationPipelineCollection(
            (new Api\Model\PipelineCompilePipelineCommandInput())->setPipeline((string) $command->pipeline),
        );

        if ($result === null) {
            throw throw new \RuntimeException('Something went wrong wile compiling the pipeline.');
        }

        return new Cloud\Event\CompiledPipeline($result->id);
    }
}
