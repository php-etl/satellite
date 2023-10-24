<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud;

use Gyroscops\Api;
use Kiboko\Component\Satellite\Cloud\DTO\AuthList;
use Kiboko\Component\Satellite\Cloud\DTO\JobCode;
use Kiboko\Component\Satellite\Cloud\DTO\Package;
use Kiboko\Component\Satellite\Cloud\DTO\PipelineId;
use Kiboko\Component\Satellite\Cloud\DTO\ProbeList;
use Kiboko\Component\Satellite\Cloud\DTO\ReferencedPipeline;
use Kiboko\Component\Satellite\Cloud\DTO\ReferencedWorkflow;
use Kiboko\Component\Satellite\Cloud\DTO\RepositoryList;
use Kiboko\Component\Satellite\Cloud\DTO\Step;
use Kiboko\Component\Satellite\Cloud\DTO\StepCode;
use Kiboko\Component\Satellite\Cloud\DTO\StepList;
use Kiboko\Component\Satellite\Cloud\DTO\WorkflowId;
use Symfony\Component\ExpressionLanguage\Expression;

final readonly class Workflow implements WorkflowInterface
{
    public function __construct(
        private Context $context,
    ) {}

    public static function fromLegacyConfiguration(array $configuration): DTO\Workflow
    {
        $random = bin2hex(random_bytes(4));

        return new DTO\Workflow(
            $configuration['pipeline']['name'] ?? sprintf('Workflow %s', $random),
            $configuration['pipeline']['code'] ?? sprintf('workflow_%s', $random),
            new DTO\JobList(
                ...array_map(
                    function (array $config, int $order) {
                        if (array_key_exists('pipeline', $config)) {
                            $name = $config['pipeline']['name'] ?? sprintf('pipeline%d', $order);
                            $code = $config['pipeline']['code'] ?? sprintf('pipeline%d', $order);
                            unset($config['pipeline']['name'], $config['pipeline']['code']);

                            array_walk_recursive($config, function (&$value): void {
                                if ($value instanceof Expression) {
                                    $value = '@='.$value;
                                }
                            });

                            return new DTO\Workflow\Pipeline(
                                $name,
                                new JobCode($code),
                                new StepList(
                                    ...array_map(fn (array $step, int $order) => new Step(
                                            $step['name'] ?? sprintf('step%d', $order),
                                            new StepCode($step['code'] ?? sprintf('step%d', $order)),
                                            $step,
                                            new ProbeList(),
                                            $order
                                        ),
                                        $config['pipeline']['steps'],
                                        range(0, (is_countable($config['pipeline']['steps']) ? \count($config['pipeline']['steps']) : 0) - 1)
                                    ),
                                ),
                                $order
                            );
                        } elseif (array_key_exists('action', $config)) {
                            $name = $config['action']['name'] ?? sprintf('pipeline%d', $order);
                            $code = $config['action']['code'] ?? sprintf('pipeline%d', $order);
                            unset($config['action']['name'], $config['action']['code']);

                            array_walk_recursive($config, function (&$value): void {
                                if ($value instanceof Expression) {
                                    $value = '@='.$value;
                                }
                            });

                            return new DTO\Workflow\Action(
                                $name,
                                new JobCode($code),
                                $config,
                                $order,
                            );
                        }
                    },
                    $configuration['workflow']['jobs'],
                    range(0, (is_countable($configuration['workflow']['jobs']) ? \count($configuration['workflow']['jobs']) : 0) - 1)
                )
            ),
            new DTO\Autoload(
                ...array_map(
                    fn (string $namespace, array $paths): DTO\PSR4AutoloadConfig => new DTO\PSR4AutoloadConfig($namespace, ...$paths['paths']),
                    array_keys($configuration['composer']['autoload']['psr4'] ?? []),
                    $configuration['composer']['autoload']['psr4'] ?? [],
                )
            ),
            new DTO\PackageList(
                ...array_map(
                    function (string $namespace) {
                        $parts = explode(':', $namespace);

                         return new Package($parts[0], $parts[1] ?? '*');
                    },
                    $configuration['composer']['require'] ?? [],
                )
            ),
            new RepositoryList(
                ...array_map(
                    fn (array $repository): DTO\Repository => new DTO\Repository($repository['name'], $repository['type'], $repository['url']),
                    $configuration['composer']['repositories'] ?? [],
                )
            ),
            new AuthList(
                ...array_map(
                    fn (array $repository): DTO\Auth => new DTO\Auth($repository['url'], $repository['token']),
                    $configuration['composer']['auth'] ?? [],
                )
            ),
        );
    }

    public static function fromApiWithId(Api\Client $client, WorkflowId $id, array $configuration): DTO\ReferencedWorkflow
    {
        $item = $client->getPipelineItem($id->asString());

        try {
            \assert($item instanceof Api\Model\PipelineRead);
        } catch (\AssertionError) {
            throw new AccessDeniedException('Could not retrieve the pipeline.');
        }

        return new ReferencedWorkflow(
            new WorkflowId($item->getId()),
            self::fromApiModel($client, $item, $configuration)
        );
    }

    public static function fromApiWithCode(Api\Client $client, string $code, array $configuration): DTO\ReferencedWorkflow
    {
        $collection = $client->getPipelineCollection(['code' => $code]);

        try {
            \assert(\is_array($collection));
        } catch (\AssertionError) {
            throw new AccessDeniedException('Could not retrieve the pipeline.');
        }
        try {
            \assert(1 === \count($collection));
            \assert($collection[0] instanceof Api\Model\PipelineRead);
        } catch (\AssertionError) {
            throw new \OverflowException('There seems to be several pipelines with the same code, please contact your Customer Success Manager.');
        }

        return new ReferencedWorkflow(
            new WorkflowId($collection[0]->getId()),
            self::fromApiModel($client, $collection[0], $configuration)
        );
    }

    private static function fromApiModel(Api\Client $client, Api\Model\PipelineRead $model, array $configuration): DTO\Workflow
    {
        $steps = $client->add($model->getId());

        try {
            \assert(\is_array($steps));
        } catch (\AssertionError) {
            throw new AccessDeniedException('Could not retrieve the pipeline steps.');
        }

        return new DTO\Workflow(
            $model->getLabel(),
            $model->getCode(),
            new DTO\JobList(
                ...array_map(function (Api\Model\PipelineStep $step, int $order) use ($client) {
                    $probes = $client->apiPipelineStepsProbesGetSubresourcePipelineStepSubresource($step->getId());

                    return new DTO\Step(
                        $step->getLabel(),
                        new StepCode($step->getCode()),
                        $step->getConfiguration(),
                        new ProbeList(
                            ...array_map(fn (Api\Model\PipelineStepProbe $probe) => new DTO\Probe($probe->getLabel(), $probe->getCode(), $probe->getOrder()), $probes)
                        ),
                        $order
                    );
                }, $steps, range(0, \count($steps)))
            ),
            new DTO\Autoload(
                ...array_map(
                    fn (string $namespace, array $paths): DTO\PSR4AutoloadConfig => new DTO\PSR4AutoloadConfig($namespace, ...$paths['paths']),
                    array_keys($configuration['composer']['autoload']['psr4'] ?? []),
                    $model->getAutoload(),
                )
            ),
            new DTO\PackageList(
                ...array_map(
                    function (string $namespace) {
                        $parts = explode(':', $namespace);

                        return new Package($parts[0], $parts[1] ?? '*');
                    },
                    $model->getPackages(),
                )
            ),
            new RepositoryList(
                ...array_map(
                    fn (array $repository): DTO\Repository => new DTO\Repository($repository['name'], $repository['type'], $repository['url']),
                    $model->getRepositories(),
                )
            ),
            new AuthList(
                ...array_map(
                    fn (array $repository): DTO\Auth => new DTO\Auth($repository['url'], $repository['token']),
                    $model->getAuths(),
                )
            ),
        );
    }

    public function create(DTO\WorkflowInterface $workflow): DTO\CommandBatch
    {
        return new DTO\CommandBatch(
            new Command\Workflow\DeclareWorkflowCommand(
                $workflow->code(),
                $workflow->label(),
                $workflow->jobs(),
                $workflow->autoload(),
                $workflow->packages(),
                $workflow->repositories(),
                $workflow->auths(),
                $this->context->organization(),
                $this->context->workspace(),
            )
        );
    }

    public function update(DTO\ReferencedWorkflow $actual, DTO\WorkflowInterface $desired): DTO\CommandBatch
    {
        if ($actual->code() !== $desired->code()) {
            throw new \RuntimeException('Code does not match between actual and desired workflow definition.');
        }
        if ($actual->label() !== $desired->label()) {
            throw new \RuntimeException('Label does not match between actual and desired workflow definition.');
        }

        // Check the changes in the list of steps
        $diff = new Diff\StepListDiff($actual->id());
        $commands = $diff->diff($actual->steps(), $desired->steps());

        return new DTO\CommandBatch(...$commands);
    }

    public function remove(DTO\WorkflowId $id): DTO\CommandBatch
    {
        return new DTO\CommandBatch(
            new Command\Workflow\RemoveWorkflowCommand($id),
        );
    }
}
