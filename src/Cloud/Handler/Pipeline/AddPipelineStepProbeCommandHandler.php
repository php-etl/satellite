<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Handler\Pipeline;

use Gyroscops\Api;
use Kiboko\Component\Satellite\Cloud;

final class AddPipelineStepProbeCommandHandler
{
    public function __construct(
        private Api\Client $client
    ) {}

    public function __invoke(Cloud\Command\Pipeline\AddPipelineStepProbeCommand $command): Cloud\Event\AddedPipelineStepProbe
    {
        $response = $this->client->addPipelineStepProbePipelineStepProbeCollection(
            (new Api\Model\PipelineStepProbeAddPipelineStepProbCommandInput())
                ->setId($command->pipeline)
                ->setCode($command->stepCode)
                ->setProbe($command->probe),
            Api\Client::FETCH_RESPONSE
        );

        if ($response !== null && $response->getStatusCode() !== 202) {
            throw throw new \RuntimeException($response->getReasonPhrase());
        }

        $result = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        return new Cloud\Event\AddedPipelineStepProbe($result["id"]);
    }
}