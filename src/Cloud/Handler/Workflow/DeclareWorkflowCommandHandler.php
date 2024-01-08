<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Handler\Workflow;

use Gyroscops\Api;
use Kiboko\Component\Satellite\Cloud;
use Kiboko\Component\Satellite\Cloud\DTO\PSR4AutoloadConfig;

final readonly class DeclareWorkflowCommandHandler
{
    public function __construct(
        private Api\Client $client,
    ) {
    }

    public function __invoke(Cloud\Command\Workflow\DeclareWorkflowCommand $command): Cloud\Event\Workflow\WorkflowDeclared
    {
        try {
            /** @var Api\Model\WorkflowDeclareWorkflowCommandJsonldRead $result */
            $result = $this->client->declareWorkflowWorkflowCollection(
                (new Api\Model\WorkflowDeclareWorkflowCommandInput())
                    ->setLabel($command->label)
                    ->setCode($command->code)
                    ->setComposer(
                        (new Api\Model\Composer())
                            ->setAutoloads($command->composer->autoload()->map(
                                fn (PSR4AutoloadConfig $autoloadConfig) => (new Api\Model\ComposerAutoload())
                                    ->setNamespace($autoloadConfig->namespace)
                                    ->setType('psr-4')
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
                    )
                ->setJobs(
                    $command->jobs->map(
                        function (Cloud\DTO\Workflow\JobInterface $job) {
                            if ($job instanceof Cloud\DTO\Workflow\Pipeline) {
                                return (new Api\Model\Job())
                                    ->setCode($job->code->asString())
                                    ->setLabel($job->label)
                                    ->setPipeline(
                                        (new Api\Model\Pipeline())
                                            ->setSteps(
                                                $job->stepList->map(
                                                    fn (Cloud\DTO\Step $step) => (new Api\Model\Step())
                                                        ->setCode($step->code->asString())
                                                        ->setLabel($step->label)
                                                        ->setConfiguration($step->config)
                                                )
                                            )
                                    )
                                ;
                            }

                            if ($job instanceof Cloud\DTO\Workflow\Action) {
                                return (new Api\Model\Job())
                                    ->setCode($job->code->asString())
                                    ->setLabel($job->label)
                                    ->setAction(
                                        (new Api\Model\Action())
                                            ->setConfiguration($job->configuration)
                                    )
                                ;
                            }

                            throw new \RuntimeException('Unexpected instance of PipelineInterface.');
                        }
                    )
                ),
            );
        } catch (Api\Exception\DeclareWorkflowWorkflowCollectionBadRequestException $exception) {
            throw new Cloud\DeclareWorkflowFailedException('Something went wrong while declaring the workflow. Maybe your client is not up to date, you may want to update your Gyroscops client.', previous: $exception);
        } catch (Api\Exception\DeclareWorkflowWorkflowCollectionUnprocessableEntityException $exception) {
            throw new Cloud\DeclareWorkflowFailedException('Something went wrong while declaring the workflow. It seems the data you sent was invalid, please check your input.', previous: $exception);
        }

        return new Cloud\Event\Workflow\WorkflowDeclared($result->getId());
    }
}
