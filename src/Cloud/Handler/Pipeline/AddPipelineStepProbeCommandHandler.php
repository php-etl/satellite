<?php

declare(strict_types=1);

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
        $result = $this->client->addPipelineStepProbePipelineCollection(
            (new Api\Model\PipelineAddPipelineStepProbCommandInput())
                ->setId((string) $command->pipeline)
                ->setCode((string) $command->stepCode)
                ->setProbe(
                    (new Api\Model\Probe())
                        ->setCode($command->probe->code)
                        ->setLabel($command->probe->label)
                ),
        );

        if ($result === null) {
            throw new Cloud\AddPipelineStepProbeConfigurationException('Something went wrong while trying to add a probe into an existing pipeline step.');
        }

        return new Cloud\Event\AddedPipelineStepProbe($result->id);
    }
}
