<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Handler\Pipeline;

use Gyroscops\Api;
use Kiboko\Component\Satellite\Cloud;
use Kiboko\Component\Satellite\Cloud\DTO\Probe;

final class AddBeforePipelineStepCommandHandler
{
    public function __construct(
        private Api\Client $client
    ) {}

    public function __invoke(Cloud\Command\Pipeline\AddBeforePipelineStepCommand $command): Cloud\Event\AddedBeforePipelineStep
    {
        $result = $this->client->addBeforePipelineStepPipelineCollection(
            (new Api\Model\PipelineAddBeforePipelineStepCommandInput())
                ->setNext((string) $command->next)
                ->setLabel($command->step->label)
                ->setCode((string) $command->step->code)
                ->setConfiguration($command->step->config)
                ->setProbes($command->step->probes->map(
                    fn (Probe $probe) => (new Api\Model\Probe())->setCode($probe->code)->setLabel($probe->label)
                ))
        );

        if ($result === null) {
            throw new Cloud\AddBeforePipelineStepConfigurationException('Something went wrong while trying to add a new step before an existing pipeline step.');
        }

        return new Cloud\Event\AddedBeforePipelineStep($result->id);
    }
}
