<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Handler\Pipeline;

use Gyroscops\Api;
use Kiboko\Component\Satellite\Cloud\Command\Pipeline\AddBeforePipelineStepCommand;
use Kiboko\Component\Satellite\Cloud\Result;

final class AddBeforePipelineStepCommandHandler
{
    public function __construct(private Api\Client $client)
    {}

    public function __invoke(AddBeforePipelineStepCommand $command): Result
    {
        $response = $this->client->addBeforePipelineStepPipelineStepCollection(
            (new Api\Model\PipelineStepAddBeforePipelineStepCommandInputJsonld())
                ->setNext($command->next)
                ->setLabel($command->label)
                ->setCode($command->code)
                ->setConfiguration($command->configuration)
                ->setProbes($command->probes)
        );

        if ($response !== null && $response->getStatusCode() !== 202) {
            throw new \RuntimeException($response->getReasonPhrase());
        }

        return new Result($response->getStatusCode(), $response->getBody()->getContents());
    }
}
