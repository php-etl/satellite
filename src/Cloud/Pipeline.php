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
    ) {
    }

    public static function fromLegacyConfiguration(array $configuration): DTO\Pipeline
    {
        $random = bin2hex(random_bytes(4));

        return new DTO\Pipeline(
            $configuration['pipeline']['name'] ?? sprintf('Pipeline %s', $random),
            $configuration['code'] ?? sprintf('pipeline_%s', $random),
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
                }, $configuration['pipeline']['steps'] ?? [], range(0, (is_countable($configuration['pipeline']['steps'] ?? []) ? \count($configuration['pipeline']['steps'] ?? []) : 0) - 1))
            ),
            new Composer(
                new DTO\Autoload(
                    ...array_map(
                        fn (int|string $namespace, mixed $paths): DTO\PSR4AutoloadConfig => new DTO\PSR4AutoloadConfig(
                            (string) $namespace,
                            ...(is_array($paths) && isset($paths['paths']) ? (array) $paths['paths'] : []),
                        ),
                        array_keys($configuration['composer']['autoload']['psr4'] ?? []),
                        $configuration['composer']['autoload']['psr4'] ?? [],
                    )
                ),
                new DTO\PackageList(
                    ...array_map(
                        function (int|string $namespace) {
                            $parts = explode(':', (string) $namespace);

                            return new Package($parts[0], $parts[1] ?? '*');
                        },
                        array_keys($configuration['composer']['require'] ?? []),
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

    public static function fromApiWithId(Api\Client $client, PipelineId $id, array $configuration): ReferencedPipeline
    {
        $item = $client->getPipelineItem($id->asString());

        if (!$item instanceof Api\Model\PipelineRead) {
            throw new AccessDeniedException('Could not retrieve the pipeline.');
        }

        return new ReferencedPipeline(
            new PipelineId($item->getId()),
            self::fromApiModel($client, $item, $configuration)
        );
    }

    public static function fromApiWithCode(Api\Client $client, string $code, array $configuration): ReferencedPipeline
    {
        $collection = $client->getPipelineCollection(['code' => $code]);

        if (!\is_array($collection)) {
            throw new AccessDeniedException('Could not retrieve the pipeline.');
        }
        if (1 !== \count($collection)) {
            throw new \OverflowException('There seems to be several pipelines with the same code, please contact your Customer Success Manager.');
        }
        $item = $collection[0];
        if (!$item instanceof Api\Model\PipelineRead) {
            throw new AccessDeniedException('Could not retrieve the pipeline.');
        }

        return new ReferencedPipeline(
            new PipelineId($item->getId()),
            self::fromApiModel($client, $item, $configuration)
        );
    }

    private static function fromApiModel(Api\Client $client, Api\Model\PipelineRead $model, array $configuration): DTO\Pipeline
    {
        // Todo : update with the new endpoint, need to update the client
        $steps = $client->apiPipelineStepsProbesGetSubresourcePipelineStepSubresource($model->getId());

        if (!\is_array($steps)) {
            throw new AccessDeniedException('Could not retrieve the pipeline steps.');
        }

        return new DTO\Pipeline(
            $model->getLabel(),
            $model->getCode(),
            new DTO\StepList(
                ...array_map(function (Api\Model\PipelineStep $step, int $order) use ($client) {
                    $probes = $client->apiPipelineStepsProbesGetSubresourcePipelineStepSubresource($step->getId());
                    $probes = \is_array($probes) ? $probes : iterator_to_array($probes);

                    return new DTO\Step(
                        $step->getLabel(),
                        new StepCode($step->getCode()),
                        $step->getConfiguration(),
                        new ProbeList(
                            ...array_map(fn (Api\Model\PipelineStepProbe $probe) => new DTO\Probe($probe->getLabel(), $probe->getCode(), $probe->getOrder() ?? 0), $probes)
                        ),
                        $order
                    );
                }, $steps, range(0, \count($steps)))
            ),
            new Composer(
                new DTO\Autoload(
                    ...array_map(
                        fn (int|string $namespace, mixed $paths): DTO\PSR4AutoloadConfig => new DTO\PSR4AutoloadConfig(
                            (string) $namespace,
                            ...(is_array($paths) && isset($paths['paths']) ? (array) $paths['paths'] : []),
                        ),
                        array_keys($model->getAutoload() ?? []),
                        $model->getAutoload() ?? [],
                    )
                ),
                new DTO\PackageList(
                    ...array_map(
                        function (int|string $namespace) {
                            $parts = explode(':', (string) $namespace);

                            return new Package($parts[0], $parts[1] ?? '*');
                        },
                        array_keys($model->getPackages() ?? []),
                    )
                ),
                new RepositoryList(
                    ...array_map(
                        fn (array $repository): DTO\Repository => new DTO\Repository($repository['name'], $repository['type'], $repository['url']),
                        $model->getRepositories() ?? [],
                    )
                ),
                new AuthList(
                    ...array_map(
                        fn (array $repository): DTO\Auth => new DTO\Auth($repository['url'], $repository['token']),
                        $model->getAuths() ?? [],
                    )
                ),
            ),
        );
    }

    public function create(DTO\PipelineInterface&DTO\SatelliteInterface $pipeline): DTO\CommandBatch
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

    public function update(ReferencedPipeline $actual, DTO\PipelineInterface&DTO\SatelliteInterface $desired): DTO\CommandBatch
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

    public function remove(PipelineId $id): DTO\CommandBatch
    {
        return new DTO\CommandBatch(
            new Command\Pipeline\RemovePipelineCommand($id),
        );
    }
}
