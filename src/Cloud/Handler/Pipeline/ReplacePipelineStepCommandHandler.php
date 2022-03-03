<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Handler\Pipeline;

use Gyroscops\Api;
use Kiboko\Component\Satellite\Cloud;

final class ReplacePipelineStepCommandHandler
{
    public function __construct(private Api\Client $client)
    {
    }

    public function __invoke(Cloud\Command\Pipeline\ReplacePipelineStepCommand $command): Cloud\Event\ReplacedPipelineStep
    {
        $response = $this->client->replacePipelineStepPipelineStepCollection(
            (new Api\Model\PipelineStepReplacePipelineStepCommandInput())
                ->setFormer($command->former)
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

        return new Cloud\Event\ReplacedPipelineStep($result["id"]);
    }
}
