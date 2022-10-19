<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Handler\Pipeline;

use Gyroscops\Api;
use Kiboko\Component\Satellite\Cloud;
use Kiboko\Component\Satellite\Cloud\DTO\Probe;

final class AddBeforePipelineStepCommandHandler
{
    private Cloud\Transformer\ConfigTransformerInterface $transformer;

    public function __construct(
        private Api\Client $client,
    ) {
        $this->transformer = new Cloud\Transformer\ConfigTransformer();
    }

    public function __invoke(Cloud\Command\Pipeline\AddBeforePipelineStepCommand $command): Cloud\Event\AddedBeforePipelineStep
    {
        try {
            /** @var \stdClass $result */
            $result = $this->client->addBeforePipelineStepPipelineCollection(
                (new Api\Model\PipelineAddBeforePipelineStepCommandInput())
                    ->setNext((string) $command->next)
                    ->setLabel($command->step->label)
                    ->setCode((string) $command->step->code)
                    ->setConfiguration($this->transformer->transform($command->step->config))
                    ->setProbes($command->step->probes->map(
                        fn (Probe $probe) => (new Api\Model\Probe())->setCode($probe->code)->setLabel($probe->label)
                    ))
            );
        } catch (Api\Exception\AddBeforePipelineStepPipelineCollectionBadRequestException $exception) {
            throw new Cloud\AddBeforePipelineStepFailedException('Something went wrong while trying to add a new step before an existing pipeline step. Maybe your client is not up to date, you may want to update your Gyroscops client.', previous: $exception);
        } catch (Api\Exception\AddBeforePipelineStepPipelineCollectionUnprocessableEntityException $exception) {
            throw new Cloud\AddBeforePipelineStepFailedException('Something went wrong while trying to add a new step before an existing pipeline step. It seems the data you sent was invalid, please check your input.', previous: $exception);
        }

        if (null === $result) {
            // TODO: change the exception message, it doesn't give enough details on how to fix the issue
            throw new Cloud\AddBeforePipelineStepFailedException('Something went wrong while trying to add a new step before an existing pipeline step.');
        }

        return new Cloud\Event\AddedBeforePipelineStep($result->id);
    }
}
