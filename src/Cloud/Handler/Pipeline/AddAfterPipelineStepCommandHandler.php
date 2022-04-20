<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Handler\Pipeline;

use Gyroscops\Api;
use Kiboko\Component\Satellite\Cloud;
use Kiboko\Component\Satellite\Cloud\DTO\Probe;

final class AddAfterPipelineStepCommandHandler
{
    public function __construct(
        private Api\Client $client
    ) {}

    public function __invoke(Cloud\Command\Pipeline\AddAfterPipelineStepCommand $command): Cloud\Event\AddedAfterPipelineStep
    {
        $result = $this->client->addAfterPipelineStepPipelineCollection(
            (new Api\Model\PipelineAddAfterPipelineStepCommandInput())
                ->setPrevious((string) $command->previous)
                ->setLabel($command->step->label)
                ->setCode((string) $command->step->code)
                ->setConfiguration($command->step->config)
                ->setProbes($command->step->probes->map(
                    fn (Probe $probe) => (new Api\Model\Probe())->setCode($probe->code)->setLabel($probe->label)
                ))
        );

        if ($result === null) {
            throw new Cloud\AddAfterPipelineStepConfigurationException('Something went wrong while trying to add a new step after an existing pipeline step.');
        }

        return new Cloud\Event\AddedAfterPipelineStep($result->id);
    }
}
