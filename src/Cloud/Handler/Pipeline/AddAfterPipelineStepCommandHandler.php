<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Handler\Pipeline;

use Gyroscops\Api;
use Kiboko\Component\Satellite\Cloud\Command\Pipeline\AddAfterPipelineStepCommand;
use Kiboko\Component\Satellite\Cloud\Result;

final class AddAfterPipelineStepCommandHandler
{
    public function __construct(private Api\Client $client)
    {}

    public function __invoke(AddAfterPipelineStepCommand $command): Result
    {
        $response = $this->client->addAfterPipelineStepPipelineStepCollection(
            (new Api\Model\PipelineStepAddAfterPipelineStepCommandInputJsonld())
                ->setPrevious($command->previous)
                ->setLabel($command->label)
                ->setCode($command->code)
                ->setConfiguration($command->configuration)
                ->setProbes($command->probes),
            Api\Client::FETCH_RESPONSE
        );

        if ($response !== null && $response->getStatusCode() !== 202) {
            throw new \RuntimeException($response->getReasonPhrase());
        }

        return new Result($response->getStatusCode(), $response->getBody()->getContents());
    }
}
