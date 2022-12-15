<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Handler\Pipeline;

use Gyroscops\Api;
use Kiboko\Component\Satellite\Cloud;
use Kiboko\Component\Satellite\Cloud\DTO\Probe;

final class ReplacePipelineStepCommandHandler
{
    public function __construct(
        private Api\Client $client,
    ) {
    }

    public function __invoke(Cloud\Command\Pipeline\ReplacePipelineStepCommand $command): Cloud\Event\ReplacedPipelineStep
    {
        try {
            /** @var \stdClass $result */
            $result = $this->client->replacePipelineStepPipelineCollection(
                (new Api\Model\PipelineReplacePipelineStepCommandInput())
                    ->setFormer((string) $command->former)
                    ->setPipeline((string) $command->pipeline)
                    ->setCode((string) $command->step->code)
                    ->setLabel($command->step->label)
                    ->setConfiguration($command->step->config)
                    ->setProbes($command->step->probes->map(
                        fn (Probe $probe) => (new Api\Model\Probe())->setCode($probe->code)->setLabel($probe->label),
                    ))
            );
        } catch (Api\Exception\ReplacePipelineStepPipelineCollectionBadRequestException $exception) {
            throw new Cloud\ReplacePipelineStepFailedException('Something went wrong while replacing a step from the pipeline. Maybe your client is not up to date, you may want to update your Gyroscops client.', previous: $exception);
        } catch (Api\Exception\ReplacePipelineStepPipelineCollectionUnprocessableEntityException $exception) {
            throw new Cloud\ReplacePipelineStepFailedException('Something went wrong while replacing a step from the pipeline. It seems the data you sent was invalid, please check your input.', previous: $exception);
        }

        if (null === $result) {
            // TODO: change the exception message, it doesn't give enough details on how to fix the issue
            throw new Cloud\ReplacePipelineStepFailedException('Something went wrong while replacing a step from the pipeline.');
        }

        return new Cloud\Event\ReplacedPipelineStep($result->id);
    }
}
