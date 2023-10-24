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
    ) {}

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
                            ->setAutoloads($command->autoload->map(
                                fn (PSR4AutoloadConfig $autoloadConfig) => (new Api\Model\ComposerAutoload())
                                    ->setNamespace($autoloadConfig->namespace)
                                    ->setPaths($autoloadConfig->paths)
                            ))
                            ->setPackages($command->packages->transform())
                            ->setAuthentications($command->auths->map(
                                fn (Cloud\DTO\Auth $auth) => (new Api\Model\ComposerAuthentication())
                                    ->setUrl($auth->url)
                                    ->setToken($auth->token)
                            ))
                            ->setRepositories($command->repositories->map(
                                fn (Cloud\DTO\Repository $repository) => (new Api\Model\ComposerRepository())
                                    ->setName($repository->name)
                                    ->setType($repository->type)
                                    ->setUrl($repository->url)
                            ))
                    ),
            );
        } catch (Api\Exception\DeclareWorkflowWorkflowCollectionBadRequestException $exception) {
            throw new Cloud\DeclarePipelineFailedException('Something went wrong while declaring the pipeline. Maybe your client is not up to date, you may want to update your Gyroscops client.', previous: $exception);
        } catch (Api\Exception\DeclareWorkflowWorkflowCollectionUnprocessableEntityException $exception) {
            throw new Cloud\DeclarePipelineFailedException('Something went wrong while declaring the pipeline. It seems the data you sent was invalid, please check your input.', previous: $exception);
        } catch (\Exception $exception) {
            var_dump($exception);
        }

        return new Cloud\Event\Workflow\WorkflowDeclared($result->getId());
    }
}
