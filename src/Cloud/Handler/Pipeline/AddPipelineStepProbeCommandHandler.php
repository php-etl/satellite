<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Handler\Pipeline;

use Gyroscops\Api;
use Kiboko\Component\Satellite\Cloud;

final readonly class AddPipelineStepProbeCommandHandler
{
    public function __construct(
        private Api\Client $client
    ) {}

    public function __invoke(Cloud\Command\Pipeline\AddPipelineStepProbeCommand $command): Cloud\Event\AddedPipelineStepProbe
    {
        try {
            /** @var \stdClass $result */
            $result = $this->client->addPipelineStepProbePipelineItem(
                $command->pipeline->asString(),
                (new Api\Model\PipelineAddPipelineStepProbCommandInput())
                    ->setProbe(
                        (new Api\Model\Probe())
                            ->setCode($command->probe->code)
                            ->setLabel($command->probe->label)
                    ),
            );
        } catch (Api\Exception\AddPipelineStepProbePipelineItemBadRequestException $exception) {
            throw new Cloud\AddPipelineStepProbeFailedException('Something went wrong while trying to add a probe into an existing pipeline step. Maybe your client is not up to date, you may want to update your Gyroscops client.', previous: $exception);
        } catch (Api\Exception\AddPipelineStepProbePipelineItemUnprocessableEntityException $exception) {
            throw new Cloud\AddPipelineStepProbeFailedException('Something went wrong while trying to add a probe into an existing pipeline step. It seems the data you sent was invalid, please check your input.', previous: $exception);
        }

        return new Cloud\Event\AddedPipelineStepProbe($result->id);
    }
}
