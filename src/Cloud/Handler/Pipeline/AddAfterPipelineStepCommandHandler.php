<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Handler\Pipeline;

use Gyroscops\Api;
use Kiboko\Component\Satellite\Cloud;

final class AddAfterPipelineStepCommandHandler
{
    public function __construct(
        private Api\Client $client
    ) {}

    public function __invoke(Cloud\Command\Pipeline\AddAfterPipelineStepCommand $command): Cloud\Event\AddedAfterPipelineStep
    {
        $result = $this->client->addAfterPipelineStepPipelineStepCollection(
            (new Api\Model\PipelineStepAddAfterPipelineStepCommandInput())
                ->setPrevious((string) $command->previous)
                ->setLabel($command->step->label)
                ->setCode($command->step->code)
                ->setConfiguration($command->step->config)
                ->setProbes($command->step->probes)
        );

        if ($result === null) {
            throw throw new \RuntimeException('Something went wrong wile adding pipeline step.');
        }

        return new Cloud\Event\AddedAfterPipelineStep($result["id"]);
    }
}
