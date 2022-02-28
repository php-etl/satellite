<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Handler\Pipeline;

use Gyroscops\Api;
use Kiboko\Component\Satellite\Cloud\Command\Pipeline\AppendPipelineStepCommand;
use Kiboko\Component\Satellite\Cloud\Result;

final class AppendPipelineStepCommandHandler
{
    public function __construct(private Api\Client $client)
    {}

    public function __invoke(AppendPipelineStepCommand $command): Result
    {
        $response = $this->client->appendPipelineStepPipelineStepCollection(
            (new Api\Model\PipelineStepAppendPipelineStepCommandInputJsonld())
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
