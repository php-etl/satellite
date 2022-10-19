<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Handler\Pipeline;

use Gyroscops\Api;
use Kiboko\Component\Satellite\Cloud;
use Kiboko\Component\Satellite\Cloud\DTO\Probe;
use Kiboko\Component\Satellite\Cloud\DTO\PSR4AutoloadConfig;
use Kiboko\Component\Satellite\Cloud\DTO\Step;

final class DeclarePipelineCommandHandler
{
    private Cloud\Transformer\ConfigTransformerInterface $transformer;

    public function __construct(
        private Api\Client $client,
    ) {
        $this->transformer = new Cloud\Transformer\ConfigTransformer();
    }

    public function __invoke(Cloud\Command\Pipeline\DeclarePipelineCommand $command): Cloud\Event\PipelineDeclared
    {
        try {
            /** @var \stdClass $result */
            $result = $this->client->declarePipelinePipelineCollection(
                (new Api\Model\PipelineDeclarePipelineCommandInput())
                    ->setLabel($command->label)
                    ->setCode($command->code)
                    ->setWorkspace((string) $command->project)
                    ->setOrganization((string) $command->organizationId)
                    ->setSteps($command->steps->map(
                        fn (Step $step) => (new Api\Model\StepInput())
                            ->setCode((string) $step->code)
                            ->setLabel($step->label)
                            ->setConfig($this->transformer->transform($step->config))
                            ->setProbes($step->probes->map(
                                fn (Probe $probe) => (new Api\Model\Probe())->setCode($probe->code)->setLabel($probe->label))
                            )
                    ))
                    ->setAutoloads($command->autoload->map(
                        fn (PSR4AutoloadConfig $autoloadConfig) => (new Api\Model\AutoloadInput())
                            ->setNamespace($autoloadConfig->namespace)
                            ->setPaths($autoloadConfig->paths)
                    ))
            );
        } catch (Api\Exception\DeclarePipelinePipelineCollectionBadRequestException $exception) {
            throw new Cloud\DeclarePipelineFailedException('Something went wrong while declaring the pipeline. Maybe your client is not up to date, you may want to update your Gyroscops client.', previous: $exception);
        } catch (Api\Exception\DeclarePipelinePipelineCollectionUnprocessableEntityException $exception) {
            throw new Cloud\DeclarePipelineFailedException('Something went wrong while declaring the pipeline. It seems the data you sent was invalid, please check your input.', previous: $exception);
        }

        if (null === $result) {
            // TODO: change the exception message, it doesn't give enough details on how to fix the issue
            throw new Cloud\DeclarePipelineFailedException('Something went wrong while declaring the pipeline.');
        }

        return new Cloud\Event\PipelineDeclared($result->id);
    }
}
