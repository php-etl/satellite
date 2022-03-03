<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Handler\Pipeline;

use Gyroscops\Api;
use Kiboko\Component\Satellite\Cloud;

final class AddAfterPipelineStepCommandHandler
{
    public function __construct(private Api\Client $client)
    {}

    public function __invoke(Cloud\Command\Pipeline\AddAfterPipelineStepCommand $command): Cloud\Event\AddedAfterPipelineStep
    {
        $response = $this->client->addAfterPipelineStepPipelineStepCollection(
            (new Api\Model\PipelineStepAddAfterPipelineStepCommandInput())
                ->setPrevious($command->previous)
                ->setLabel($command->label)
                ->setCode($command->code)
                ->setConfiguration($command->configuration)
                ->setProbes($command->probes),
            Api\Client::FETCH_RESPONSE
        );

        if ($response !== null && $response->getStatusCode() !== 202) {
            throw throw new \RuntimeException($response->getReasonPhrase());
        }

        $result = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        return new Cloud\Event\AddedAfterPipelineStep($result["id"]);
    }
}
