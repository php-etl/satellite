<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Handler\Pipeline;

use Gyroscops\Api;
use Kiboko\Component\Satellite\Cloud\Command\Pipeline\ReplacePipelineStepCommand;
use Kiboko\Component\Satellite\Cloud\Result;
use Psr\Http\Message\ResponseInterface;

final class ReplacePipelineStepCommandHandler
{
    public function __construct(private Api\Client $client)
    {}

    public function __invoke(ReplacePipelineStepCommand $command): Result
    {
        $response = $this->client->replacePipelineStepPipelineStepCollection(
            (new Api\Model\PipelineStepReplacePipelineStepCommandInputJsonld())
                ->setId($command->pipeline)
                ->setCode($command->code)
                ->setLabel($command->label)
                ->setConfiguration($command->configuration)
                ->setProbes($command->probes)
        );

        if ($response !== null && $response->getStatusCode() !== 202) {
            throw new \RuntimeException($response->getReasonPhrase());
        }

        return new Result($response->getStatusCode(), $response->getBody()->getContents());
    }
}
