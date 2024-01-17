<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Handler\Pipeline;

use Gyroscops\Api;
use Kiboko\Component\Satellite\Cloud;
use Kiboko\Component\Satellite\Cloud\DTO\Probe;

final readonly class ReplacePipelineStepCommandHandler
{
    public function __construct(
        private Api\Client $client,
    ) {
    }

    public function __invoke(Cloud\Command\Pipeline\ReplacePipelineStepCommand $command): Cloud\Event\ReplacedPipelineStep
    {
        try {
            /** @var \stdClass $result */
            $result = $this->client->replacePipelineStepPipelineItem(
                $command->pipeline->asString(),
                (new Api\Model\PipelineReplacePipelineStepCommandInput())
                    ->setCode((string) $command->step->code)
                    ->setLabel($command->step->label)
                    ->setConfiguration($command->step->config)
                    ->setProbes($command->step->probes->map(
                        fn (Probe $probe) => (new Api\Model\Probe())->setCode($probe->code)->setLabel($probe->label),
                    ))
            );
        } catch (Api\Exception\ReplacePipelineStepPipelineItemBadRequestException $exception) {
            throw new Cloud\ReplacePipelineStepFailedException('Something went wrong while replacing a step from the pipeline. Maybe your client is not up to date, you may want to update your Gyroscops client.', previous: $exception);
        } catch (Api\Exception\ReplacePipelineStepPipelineItemUnprocessableEntityException $exception) {
            throw new Cloud\ReplacePipelineStepFailedException('Something went wrong while replacing a step from the pipeline. It seems the data you sent was invalid, please check your input.', previous: $exception);
        }

        return new Cloud\Event\ReplacedPipelineStep($result->id);
    }
}
