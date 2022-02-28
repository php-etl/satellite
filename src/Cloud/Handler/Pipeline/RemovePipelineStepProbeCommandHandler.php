<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Handler\Pipeline;

use Gyroscops\Api;
use Kiboko\Component\Satellite\Cloud\Command\Pipeline\RemovePipelineStepProbeCommand;
use Kiboko\Component\Satellite\Cloud\Result;
use Psr\Http\Message\ResponseInterface;

final class RemovePipelineStepProbeCommandHandler
{
    public function __construct(private Api\Client $client)
    {}

    public function __invoke(RemovePipelineStepProbeCommand $command): Result
    {
        $response = $this->client->removePipelineStepProbePipelineStepProbeCollection(
            (new Api\Model\PipelineStepProbeRemovePipelineStepProbCommandInputJsonld())
                ->setId($command->pipeline)
                ->setCode($command->stepCode)
                ->setProbe($command->probe)
        );

        if ($response !== null && $response->getStatusCode() !== 202) {
            throw new \RuntimeException($response->getReasonPhrase());
        }

        return new Result($response->getStatusCode(), $response->getBody()->getContents());
    }
}
