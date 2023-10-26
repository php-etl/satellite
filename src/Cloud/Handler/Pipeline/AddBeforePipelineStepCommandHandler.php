<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Handler\Pipeline;

use Gyroscops\Api;
use Kiboko\Component\Satellite\Cloud;
use Kiboko\Component\Satellite\Cloud\DTO\Probe;

final readonly class AddBeforePipelineStepCommandHandler
{
    public function __construct(
        private Api\Client $client,
    ) {
    }

    public function __invoke(Cloud\Command\Pipeline\AddBeforePipelineStepCommand $command): Cloud\Event\AddedBeforePipelineStep
    {
        try {
            /** @var \stdClass $result */
            $result = $this->client->addBeforePipelineStepPipelineItem(
                $command->pipeline->asString(),
                (new Api\Model\PipelineAddBeforePipelineStepCommandInput())
                    ->setLabel($command->step->label)
                    ->setCode((string) $command->step->code)
                    ->setConfiguration($command->step->config)
                    ->setProbes($command->step->probes->map(
                        fn (Probe $probe) => (new Api\Model\Probe())->setCode($probe->code)->setLabel($probe->label)
                    ))
            );
        } catch (Api\Exception\AddBeforePipelineStepPipelineItemBadRequestException $exception) {
            throw new Cloud\AddBeforePipelineStepFailedException('Something went wrong while trying to add a new step before an existing pipeline step. Maybe your client is not up to date, you may want to update your Gyroscops client.', previous: $exception);
        } catch (Api\Exception\AddBeforePipelineStepPipelineItemUnprocessableEntityException $exception) {
            throw new Cloud\AddBeforePipelineStepFailedException('Something went wrong while trying to add a new step before an existing pipeline step. It seems the data you sent was invalid, please check your input.', previous: $exception);
        }

        return new Cloud\Event\AddedBeforePipelineStep($result->id);
    }
}
