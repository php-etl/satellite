<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Handler\Pipeline;

use Gyroscops\Api;
use Kiboko\Component\Satellite\Cloud;
use Kiboko\Component\Satellite\Cloud\DTO\Probe;

final class AddAfterPipelineStepCommandHandler
{
    public function __construct(
        private Api\Client $client,
    ) {
    }

    public function __invoke(Cloud\Command\Pipeline\AddAfterPipelineStepCommand $command): Cloud\Event\AddedAfterPipelineStep
    {
        try {
            /** @var \stdClass $result */
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
        } catch (Api\Exception\AddAfterPipelineStepPipelineCollectionBadRequestException $exception) {
            throw new Cloud\AddAfterPipelineStepFailedException('Something went wrong while trying to add a new step after an existing pipeline step. Maybe your client is not up to date, you may want to update your Gyroscops client.', previous: $exception);
        } catch (Api\Exception\AddAfterPipelineStepPipelineCollectionUnprocessableEntityException $exception) {
            throw new Cloud\AddAfterPipelineStepFailedException('Something went wrong while trying to add a new step after an existing pipeline step. It seems the data you sent was invalid, please check your input.', previous: $exception);
        }

        if (null === $result) {
            // TODO: change the exception message, it doesn't give enough details on how to fix the issue
            throw new Cloud\AddAfterPipelineStepFailedException('Something went wrong while trying to add a new step after an existing pipeline step.');
        }

        return new Cloud\Event\AddedAfterPipelineStep($result->id);
    }
}
