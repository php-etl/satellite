<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Handler\Pipeline;

use Gyroscops\Api;
use Kiboko\Component\Satellite\Cloud;
use Kiboko\Component\Satellite\Cloud\DTO\Probe;
use Kiboko\Component\Satellite\Cloud\DTO\PSR4AutoloadConfig;
use Kiboko\Component\Satellite\Cloud\DTO\Step;

final readonly class DeclarePipelineCommandHandler
{
    public function __construct(
        private Api\Client $client,
    ) {
    }

    public function __invoke(Cloud\Command\Pipeline\DeclarePipelineCommand $command): Cloud\Event\PipelineDeclared
    {
        try {
            /** @var \stdClass $result */
            $result = $this->client->declarePipelinePipelineCollection(
                (new Api\Model\PipelineDeclarePipelineCommandInput())
                    ->setLabel($command->label)
                    ->setCode($command->code)
                    ->setSteps($command->steps->map(
                        fn (Step $step) => (new Api\Model\Step())
                            ->setCode((string) $step->code)
                            ->setLabel($step->label)
                            ->setConfiguration($step->config)
                            ->setProbes($step->probes->map(
                                fn (Probe $probe) => (new Api\Model\Probe())->setCode($probe->code)->setLabel($probe->label))
                            )
                    ))
                    ->setComposer(
                        (new Api\Model\Composer())
                            ->setAutoloads($command->composer->autoload()->map(
                                fn (PSR4AutoloadConfig $autoloadConfig) => (new Api\Model\ComposerAutoload())
                                    ->setNamespace($autoloadConfig->namespace)
                                    ->setPaths($autoloadConfig->paths)
                            ))
                            ->setPackages($command->composer->packages()->transform())
                            ->setAuthentications($command->composer->auths()->map(
                                fn (Cloud\DTO\Auth $auth) => (new Api\Model\ComposerAuthentication())
                                    ->setUrl($auth->url)
                                    ->setToken($auth->token)
                            ))
                            ->setRepositories($command->composer->repositories()->map(
                                fn (Cloud\DTO\Repository $repository) => (new Api\Model\ComposerRepository())
                                    ->setName($repository->name)
                                    ->setType($repository->type)
                                    ->setUrl($repository->url)
                            ))
                    ),
            );
        } catch (Api\Exception\DeclarePipelinePipelineCollectionBadRequestException $exception) {
            throw new Cloud\DeclarePipelineFailedException('Something went wrong while declaring the pipeline. Maybe your client is not up to date, you may want to update your Gyroscops client.', previous: $exception);
        } catch (Api\Exception\DeclarePipelinePipelineCollectionUnprocessableEntityException $exception) {
            throw new Cloud\DeclarePipelineFailedException('Something went wrong while declaring the pipeline. It seems the data you sent was invalid, please check your input.', previous: $exception);
        }

        return new Cloud\Event\PipelineDeclared($result->id);
    }
}
