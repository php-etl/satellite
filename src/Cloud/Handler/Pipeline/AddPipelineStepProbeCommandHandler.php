<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Handler\Pipeline;

use Gyroscops\Api;
use Kiboko\Component\Satellite\Cloud\Command\Pipeline\AddPipelineStepProbeCommand;
use Kiboko\Component\Satellite\Cloud\Result;

final class AddPipelineStepProbeCommandHandler
{
    public function __construct(private Api\Client $client)
    {}

    public function __invoke(AddPipelineStepProbeCommand $command): Result
    {
        $response = $this->client->addPipelineStepProbePipelineStepProbeCollection(
            (new Api\Model\PipelineStepProbeAddPipelineStepProbCommandInputJsonld())
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
