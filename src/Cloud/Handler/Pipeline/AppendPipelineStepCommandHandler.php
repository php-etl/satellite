<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Handler\Pipeline;

use Gyroscops\Api;
use Kiboko\Component\Satellite\Cloud;

final class AppendPipelineStepCommandHandler
{
    public function __construct(
        private Api\Client $client
    ) {}

    public function __invoke(Cloud\Command\Pipeline\AppendPipelineStepCommand $command): Cloud\Event\AppendedPipelineStep
    {
        $response = $this->client->appendPipelineStepPipelineStepCollection(
            (new Api\Model\PipelineStepAppendPipelineStepCommandInput())
                ->setPipeline($command->pipeline)
                ->setCode($command->code)
                ->setLabel($command->label)
                ->setConfiguration($command->configuration)
                ->setProbes($command->probes),
            Api\Client::FETCH_RESPONSE
        );

        if ($response !== null && $response->getStatusCode() !== 202) {
            throw throw new \RuntimeException($response->getReasonPhrase());
        }

        $result = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        return new Cloud\Event\AppendedPipelineStep($result["id"]);
    }
}
