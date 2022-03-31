<?php declare(strict_types=1);

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
        $response = $this->client->pipelineCompilationPipelineCollection(
            (new Api\Model\PipelineCompilePipelineCommandInput())->setPipeline((string) $command->pipeline),
            Api\Client::FETCH_RESPONSE
        );

        if ($response !== null && $response->getStatusCode() !== 202) {
            throw throw new \RuntimeException($response->getReasonPhrase());
        }

        $result = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        return new Cloud\Event\CompiledPipeline($result["id"]);
    }
}
