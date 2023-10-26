<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud;

use Gyroscops\Api;
use Kiboko\Component\Satellite\Cloud\DTO\AuthList;
use Kiboko\Component\Satellite\Cloud\DTO\Composer;
use Kiboko\Component\Satellite\Cloud\DTO\Package;
use Kiboko\Component\Satellite\Cloud\DTO\PipelineId;
use Kiboko\Component\Satellite\Cloud\DTO\ProbeList;
use Kiboko\Component\Satellite\Cloud\DTO\ReferencedPipeline;
use Kiboko\Component\Satellite\Cloud\DTO\RepositoryList;
use Kiboko\Component\Satellite\Cloud\DTO\StepCode;
use Symfony\Component\ExpressionLanguage\Expression;

final readonly class Pipeline implements PipelineInterface
{
    public function __construct(
        private Context $context,
    ) {}

    public static function fromLegacyConfiguration(array $configuration): DTO\Pipeline
    {
        $random = bin2hex(random_bytes(4));

        return new DTO\Pipeline(
            $configuration['pipeline']['name'] ?? sprintf('Pipeline %s', $random),
            $configuration['pipeline']['code'] ?? sprintf('pipeline_%s', $random),
            new DTO\StepList(
                ...array_map(function (array $stepConfig, int $order) {
                    $name = $stepConfig['name'] ?? sprintf('step%d', $order);
                    $code = $stepConfig['code'] ?? sprintf('step%d', $order);
                    unset($stepConfig['name'], $stepConfig['code']);

                    array_walk_recursive($stepConfig, function (&$value): void {
                        if ($value instanceof Expression) {
                            $value = '@='.$value;
                        }
                    });

                    return new DTO\Step(
                        $name,
                        new StepCode($code),
                        $stepConfig,
                        new ProbeList(
                            // FIXME: add probes
                        ),
                        $order,
                    );
                }, $configuration['pipeline']['steps'], range(0, (is_countable($configuration['pipeline']['steps']) ? \count($configuration['pipeline']['steps']) : 0) - 1))
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

                            return new Package($parts[0], $parts[1]);
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

    public static function fromApiWithId(Api\Client $client, PipelineId $id, array $configuration): DTO\ReferencedPipeline
    {
        $item = $client->getPipelineItem($id->asString());

        try {
            \assert($item instanceof Api\Model\PipelineRead);
        } catch (\AssertionError) {
            throw new AccessDeniedException('Could not retrieve the pipeline.');
        }

        return new ReferencedPipeline(
            new PipelineId($item->getId()),
            self::fromApiModel($client, $item, $configuration)
        );
    }

    public static function fromApiWithCode(Api\Client $client, string $code, array $configuration): DTO\ReferencedPipeline
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

        return new ReferencedPipeline(
            new PipelineId($collection[0]->getId()),
            self::fromApiModel($client, $collection[0], $configuration)
        );
    }

    private static function fromApiModel(Api\Client $client, Api\Model\PipelineRead $model, array $configuration): DTO\Pipeline
    {
        // Todo : update with the new endpoint, need to update the client
        $steps = $client->apiPipelineStepsProbesGetSubresourcePipelineStepSubresource($model->getId());

        try {
            \assert(\is_array($steps));
        } catch (\AssertionError) {
            throw new AccessDeniedException('Could not retrieve the pipeline steps.');
        }

        return new DTO\Pipeline(
            $model->getLabel(),
            $model->getCode(),
            new DTO\StepList(
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
            new Composer(
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

                            return new Package($parts[0], $parts[1]);
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
            ),
        );
    }

    public function create(DTO\SatelliteInterface&DTO\PipelineInterface $pipeline): DTO\CommandBatch
    {
        return new DTO\CommandBatch(
            new Command\Pipeline\DeclarePipelineCommand(
                $pipeline->code(),
                $pipeline->label(),
                $pipeline->steps(),
                $pipeline->composer(),
                $this->context->organization(),
                $this->context->workspace(),
            )
        );
    }

    public function update(DTO\ReferencedPipeline $actual, DTO\SatelliteInterface&DTO\PipelineInterface $desired): DTO\CommandBatch
    {
        if ($actual->code() !== $desired->code()) {
            throw new \RuntimeException('Code does not match between actual and desired pipeline definition.');
        }
        if ($actual->label() !== $desired->label()) {
            throw new \RuntimeException('Label does not match between actual and desired pipeline definition.');
        }

        // Check the changes in the list of steps
        $diff = new Diff\StepListDiff($actual->id());
        $commands = $diff->diff($actual->steps(), $desired->steps());

        // Check the changes in the list of autoloads
        if (\count($actual->autoload()) !== \count($desired->composer()->autoload())) {
            // TODO: make diff of the autoload
        }

        return new DTO\CommandBatch(...$commands);
    }

    public function remove(DTO\PipelineId $id): DTO\CommandBatch
    {
        return new DTO\CommandBatch(
            new Command\Pipeline\RemovePipelineCommand($id),
        );
    }
}
