<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud;

use Gyroscops\Api;
use Kiboko\Component\Satellite\Cloud\DTO\AuthList;
use Kiboko\Component\Satellite\Cloud\DTO\Composer;
use Kiboko\Component\Satellite\Cloud\DTO\JobCode;
use Kiboko\Component\Satellite\Cloud\DTO\Package;
use Kiboko\Component\Satellite\Cloud\DTO\ProbeList;
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
    ) {
    }

    public static function fromLegacyConfiguration(array $configuration): DTO\Workflow
    {
        $random = bin2hex(random_bytes(4));

        return new DTO\Workflow(
            $configuration['workflow']['name'] ?? sprintf('Workflow %s', $random),
            $configuration['code'] ?? sprintf('workflow%s', $random),
            new DTO\JobList(
                ...array_map(
                    function (string $code, array $config, int $order) {
                        if (\array_key_exists('pipeline', $config)) {
                            $name = $config['pipeline']['name'] ?? sprintf('pipeline%d', $order);
                            unset($config['pipeline']['name'], $config['pipeline']['code']);

                            array_walk_recursive(
                                $config,
                                function (&$value, $key) {
                                    if ($value instanceof Expression && $key === 'expression') {
                                        $value = (string) $value;
                                    }

                                    if ($value instanceof Expression) {
                                        $value = '@=' . $value;
                                    }

                                    return $value;
                                }
                            );

                            return new DTO\Workflow\Pipeline(
                                $name,
                                new JobCode($code),
                                new StepList(
                                    ...array_map(fn(string $code, array $step, int $order) => new Step(
                                        $step['name'] ?? sprintf('step%d', $order),
                                        new StepCode($code ?? sprintf('step%d', $order)),
                                        $step,
                                        new ProbeList(),
                                        $order
                                    ),
                                        array_keys($config['pipeline']['steps']),
                                        $config['pipeline']['steps'],
                                        range(0, (is_countable($config['pipeline']['steps']) ? \count($config['pipeline']['steps']) : 0) - 1)
                                    ),
                                ),
                                $order
                            );
                        }

                        if (\array_key_exists('action', $config)) {
                            $name = $config['action']['name'] ?? sprintf('action%d', $order);
                            unset($config['action']['name'], $config['action']['code']);

                            array_walk_recursive(
                                $config,
                                function (&$value, $key) {
                                    if ($value instanceof Expression && $key === 'expression') {
                                        $value = (string) $value;
                                    }

                                    if ($value instanceof Expression) {
                                        $value = '@=' . $value;
                                    }

                                    return $value;
                                }
                            );

                            $configuration = $config['action'];
                            unset($config['action']);
                            $config += $configuration;

                            return new DTO\Workflow\Action(
                                $name,
                                new JobCode($code),
                                $config,
                                $order,
                            );
                        }

                        throw new \RuntimeException('This type is currently not supported.');
                    },
                    array_keys($configuration['workflow']['jobs']),
                    $configuration['workflow']['jobs'],
                    range(0, (is_countable($configuration['workflow']['jobs']) ? \count($configuration['workflow']['jobs']) : 0) - 1),
                )
            ),
            new Composer(
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
            ),
        );
    }

    public static function fromApiWithId(Api\Client $client, WorkflowId $id): ReferencedWorkflow
    {
        /** @var Api\Model\WorkflowJsonldRead|Api\Model\WorkflowRead $item */
        $item = $client->getWorkflowItem($id->asString());

        try {
            \assert($item instanceof Api\Model\PipelineRead);
        } catch (\AssertionError) {
            throw new AccessDeniedException('Could not retrieve the workflow.');
        }

        return new ReferencedWorkflow(
            new WorkflowId($item->getId()),
            self::fromApiModel($client, $item)
        );
    }

    public static function fromApiWithCode(Api\Client $client, string $code): ReferencedWorkflow
    {
        $collection = $client->getWorkflowCollection(['code' => $code]);

        try {
            \assert(\is_array($collection));
        } catch (\AssertionError) {
            throw new AccessDeniedException('Could not retrieve the workflow.');
        }

        try {
            \assert(1 === \count($collection));
            \assert($collection[0] instanceof Api\Model\WorkflowRead);
        } catch (\AssertionError) {
            throw new \OverflowException('There seems to be several workflows with the same code, please contact your Customer Success Manager.');
        }

        return new ReferencedWorkflow(
            new WorkflowId($collection[0]->getId()),
            self::fromApiModel($client, $collection[0])
        );
    }

    private static function fromApiModel(Api\Client $client, Api\Model\WorkflowRead $model): DTO\Workflow
    {
        /** @var Api\Model\WorkflowJsonldRead|Api\Model\WorkflowRead $workflow */
        $workflow = $client->getWorkflowItem($model->getId());

        return new DTO\Workflow(
            $workflow->getLabel(),
            $workflow->getCode(),
            new DTO\JobList(
                ...array_map(function (Api\Model\Job $job, int $order) {
                    if (null !== $job->getPipeline()) {
                        return new DTO\Workflow\Pipeline(
                            $job->getLabel(),
                            new JobCode($job->getCode()),
                            new StepList(...array_map(
                                fn (Api\Model\PipelineStepRead $step, int $order) => new Step(
                                    $step->getLabel(),
                                    new StepCode($step->getCode()),
                                    $step->getConfiguration(),
                                    /* TODO : implement probes when it is enabled */
                                    new ProbeList(),
                                    $order
                                ),
                                $steps = $job->getPipeline()->getSteps(),
                                range(0, \count((array) $steps)),
                            )),
                            $order
                        );
                    }

                    if (null !== $job->getAction()) {
                        return new DTO\Workflow\Action(
                            $job->getLabel(),
                            new JobCode($job->getCode()),
                            $job->getAction()->getConfiguration(),
                            $order,
                        );
                    }

                    throw new \RuntimeException('This type of job is not currently supported.');
                }, $jobs = $workflow->getJobs(), range(0, \count((array) $jobs)))
            ),
            new Composer(
                new DTO\Autoload(
                    ...array_map(
                        fn (string $namespace, array $paths): DTO\PSR4AutoloadConfig => new DTO\PSR4AutoloadConfig($namespace, ...$paths['paths']),
                        array_keys($workflow->getAutoload()),
                        $model->getAutoload(),
                    )
                ),
                new DTO\PackageList(
                    ...array_map(
                        function (string $namespace) {
                            $parts = explode(':', $namespace);

                            return new Package($parts[0], $parts[1] ?? '*');
                        },
                        $workflow->getPackages(),
                    )
                ),
                new RepositoryList(
                    ...array_map(
                        fn (array $repository): DTO\Repository => new DTO\Repository($repository['name'], $repository['type'], $repository['url']),
                        $workflow->getRepositories(),
                    )
                ),
                new AuthList(
                    ...array_map(
                        fn (array $repository): DTO\Auth => new DTO\Auth($repository['url'], $repository['token']),
                        $workflow->getAuths(),
                    )
                ),
            ),
        );
    }

    public function create(DTO\SatelliteInterface&DTO\WorkflowInterface $workflow): DTO\CommandBatch
    {
        return new DTO\CommandBatch(
            new Command\Workflow\DeclareWorkflowCommand(
                $workflow->code(),
                $workflow->label(),
                $workflow->jobs(),
                $workflow->composer(),
                $this->context->organization(),
                $this->context->workspace(),
            )
        );
    }

    public function update(ReferencedWorkflow $actual, DTO\SatelliteInterface&DTO\WorkflowInterface $desired): DTO\CommandBatch
    {
        if ($actual->code() !== $desired->code()) {
            throw new \RuntimeException('Code does not match between actual and desired workflow definition.');
        }
        if ($actual->label() !== $desired->label()) {
            throw new \RuntimeException('Label does not match between actual and desired workflow definition.');
        }

        $diff = new Diff\JobListDiff($actual->id());
        $commands = $diff->diff($actual->jobs(), $desired->jobs());

        return new DTO\CommandBatch(...$commands);
    }

    public function remove(WorkflowId $id): DTO\CommandBatch
    {
        return new DTO\CommandBatch(
            new Command\Workflow\RemoveWorkflowCommand($id),
        );
    }
}
