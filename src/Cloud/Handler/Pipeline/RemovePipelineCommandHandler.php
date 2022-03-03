<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Handler\Pipeline;

use Gyroscops\Api;
use Kiboko\Component\Satellite\Cloud;

final class RemovePipelineCommandHandler
{
    public function __construct(private Api\Client $client)
    {}

    public function __invoke(Cloud\Command\Pipeline\RemovePipelineStepCommand $command): Cloud\Event\RemovedPipeline
    {
        $response = $this->client->deletePipelinePipelineItem($command->pipeline);

        if ($response !== null && $response->getStatusCode() !== 204) {
            throw throw new \RuntimeException($response->getReasonPhrase());
        }

        $result = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

        return new Cloud\Event\RemovedPipeline($result["id"]);
    }
}
