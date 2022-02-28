<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Handler\Pipeline;

use Gyroscops\Api;
use Kiboko\Component\Satellite\Cloud\Command\Pipeline\CompilePipelineCommand;
use Kiboko\Component\Satellite\Cloud\Result;

final class CompilePipelineCommandHandler
{
    public function __construct(private Api\Client $client)
    {
    }

    public function __invoke(CompilePipelineCommand $command): Result
    {
        $response = $this->client->pipelineCompilationPipelineCollection(
            (new Api\Model\PipelineCompilePipelineCommandInputJsonld())->setId($command->pipeline)
        );

        if ($response !== null && $response->getStatusCode() !== 202) {
            throw new \RuntimeException($response->getReasonPhrase());
        }

        return new Result($response->getStatusCode(), $response->getBody()->getContents());
    }
}
