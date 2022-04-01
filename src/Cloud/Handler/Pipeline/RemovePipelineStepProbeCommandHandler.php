<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Handler\Pipeline;

use Gyroscops\Api;
use Kiboko\Component\Satellite\Cloud;

final class RemovePipelineStepProbeCommandHandler
{
    public function __construct(
        private Api\Client $client
    ) {}

    public function __invoke(Cloud\Command\Pipeline\RemovePipelineStepProbeCommand $command): Cloud\Event\RemovedPipelineStepProbe
    {
        $result = $this->client->removePipelineStepProbePipelineItem(
            (string) $command->stepCode,
            $command->probe->code,
            $command->probe->label,
            (string) $command->pipeline,
        );

        if ($result === null) {
            throw throw new \RuntimeException('Something went wrong wile removing a probe from the step.');
        }

        return new Cloud\Event\RemovedPipelineStepProbe($result->id);
    }
}
