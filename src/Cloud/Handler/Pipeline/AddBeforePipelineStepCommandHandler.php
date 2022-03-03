<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Handler\Pipeline;

use Gyroscops\Api;
use Kiboko\Component\Satellite\Cloud;

final class AddBeforePipelineStepCommandHandler
{
    public function __construct(private Api\Client $client)
    {}

    public function __invoke(Cloud\Command\Pipeline\AddBeforePipelineStepCommand $command): Cloud\Event\AddedBeforePipelineStep
    {
        $response = $this->client->addBeforePipelineStepPipelineStepCollection(
            (new Api\Model\PipelineStepAddBeforePipelineStepCommandInput())
                ->setNext($command->next)
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

        return new Cloud\Event\AddedBeforePipelineStep($result["id"]);
    }
}
