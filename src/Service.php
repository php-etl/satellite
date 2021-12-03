<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite;

use Kiboko\Component\Packaging;
use Kiboko\Component\Satellite;
use Kiboko\Contract\Configurator;
use PhpParser\Node;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception as Symfony;
use Symfony\Component\Config\Definition\Processor;
use Kiboko\Component\SatelliteToolbox;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

final class Service implements Configurator\FactoryInterface
{
    private Processor $processor;
    private Satellite\Configuration $configuration;
    private ExpressionLanguage $interpreter;
    /** @var array<string, Satellite\Adapter\FactoryInterface> */
    private array $adapters = [];
    /** @var array<string, Satellite\Runtime\FactoryInterface> */
    private array $runtimes = [];
    /** @var array<string, Configurator\FactoryInterface> */
    private array $features = [];
    /** @var array<string, Configurator\FactoryInterface> */
    private array $pipelines = [];
    /** @var array<string, Satellite\Pipeline\ConfigurationApplier> */
    private array $plugins = [];

    /** @var callable(interpreter: ExpressionLanguage): Configurator\FactoryInterface ...$factories */
    public function __construct(
        callable ...$factories
    ) {
        $this->processor = new Processor();
        $this->configuration = new Satellite\Configuration();
        $this->interpreter = new Satellite\ExpressionLanguage\ExpressionLanguage();

        $this
            ->registerAdapters(
                new Adapter\Docker\Factory(),
                new Adapter\Filesystem\Factory(),
            )
            ->registerRuntimes(
                new Runtime\Api\Factory(),
                new Runtime\HttpHook\Factory(),
                new Runtime\Pipeline\Factory(),
                new Runtime\Workflow\Factory(),
            )
            ->registerFactories(
                fn (ExpressionLanguage $interpreter) => new Satellite\Feature\Logger\Service($interpreter),
                fn (ExpressionLanguage $interpreter) => new Satellite\Feature\State\Service($this->interpreter),
                fn (ExpressionLanguage $interpreter) => new Satellite\Feature\Rejection\Service($this->interpreter),
                fn (ExpressionLanguage $interpreter) => new Satellite\Plugin\Custom\Service($this->interpreter),
                fn (ExpressionLanguage $interpreter) => new Satellite\Plugin\Stream\Service($this->interpreter),
                fn (ExpressionLanguage $interpreter) => new Satellite\Plugin\SFTP\Service($this->interpreter),
                fn (ExpressionLanguage $interpreter) => new Satellite\Plugin\FTP\Service($this->interpreter),
                fn (ExpressionLanguage $interpreter) => new Satellite\Plugin\Batching\Service($this->interpreter),
                ...$factories
            );
    }

    private function addAdapter(Configurator\Adapter $attribute, Satellite\Adapter\FactoryInterface $adapter): self
    {
        $this->adapters[$attribute->name] = $adapter;
        $this->configuration->addAdapter($attribute->name, $adapter->configuration());

        return $this;
    }

    private function addRuntime(Configurator\Runtime $attribute, Satellite\Runtime\FactoryInterface $runtime): self
    {
        $this->runtimes[$attribute->name] = $runtime;
        $this->configuration->addRuntime($attribute->name, $runtime->configuration());

        foreach ($this->features as $name => $feature) {
            $runtime->addFeature($name, $feature);
        }
        foreach ($this->pipelines as $name => $plugin) {
            $runtime->addPlugin($name, $plugin);
        }

        return $this;
    }

    private function addFeature(Configurator\Feature $attribute, Configurator\FactoryInterface $feature): self
    {
        $this->features[$attribute->name] = $feature;
        foreach ($this->runtimes as $runtime) {
            $runtime->addFeature($attribute->name, $feature);
        }

        return $this;
    }

    /** @param Configurator\FactoryInterface $plugin */
    private function addPipeline(
        Configurator\Pipeline $attribute,
        Configurator\FactoryInterface $plugin,
        ExpressionLanguage $interpreter,
    ): self {
        $this->configuration->addPlugin($attribute->name, $plugin->configuration());
        $this->pipelines[$attribute->name] = $plugin;

        $this->plugins[$attribute->name] = $applier = new Satellite\Pipeline\ConfigurationApplier($attribute->name, $plugin, $interpreter);
        $applier->withPackages(...$attribute->dependencies);

        foreach ($attribute->steps as $step) {
            if ($step instanceof Configurator\Pipeline\StepExtractor) {
                $applier->withExtractor($step->name);
            }
            if ($step instanceof Configurator\Pipeline\StepTransformer) {
                $applier->withTransformer($step->name);
            }
            if ($step instanceof Configurator\Pipeline\StepLoader) {
                $applier->withLoader($step->name);
            }
        }

        return $this;
    }

    public function registerAdapters(Satellite\Adapter\FactoryInterface ...$adapters): self
    {
        foreach ($adapters as $adapter) {
            /** @var Configurator\Adapter $attribute */
            try {
                foreach (expectAttributes($adapter, Configurator\Adapter::class) as $attribute) {
                    $this->addAdapter($attribute, $adapter);
                }
            } catch (MissingAttributeException $exception) {
            }
        }

        return $this;
    }

    public function registerRuntimes(Satellite\Runtime\FactoryInterface ...$runtimes): self
    {
        foreach ($runtimes as $runtime) {
            /** @var Configurator\Runtime $attribute */
            foreach (expectAttributes($runtime, Configurator\Runtime::class) as $attribute) {
                $this->addRuntime($attribute, $runtime);
            }
        }

        return $this;
    }

    /** @var callable(interpreter: ExpressionLanguage): Configurator\FactoryInterface ...$factories */
    public function registerFactories(callable ...$factories): self
    {
        foreach ($factories as $factory) {
            $plugin = $factory($interpreter = clone $this->interpreter);

            /** @var Configurator\Feature $attribute */
            foreach (extractAttributes($plugin, Configurator\Feature::class) as $attribute) {
                $this->addFeature($attribute, $plugin);
            }

            /** @var Configurator\Pipeline $attribute */
            foreach (extractAttributes($plugin, Configurator\Pipeline::class) as $attribute) {
                $this->addPipeline($attribute, $plugin, $interpreter);
            }
        }

        return $this;
    }

    public function configuration(): ConfigurationInterface
    {
        return $this->configuration;
    }

    /**
     * @throws Configurator\ConfigurationExceptionInterface
     */
    public function normalize(array $config): array
    {
        try {
            return $this->processor->processConfiguration($this->configuration, $config);
        } catch (Symfony\InvalidTypeException | Symfony\InvalidConfigurationException $exception) {
            throw new Configurator\InvalidConfigurationException($exception->getMessage(), 0, $exception);
        }
    }

    public function validate(array $config): bool
    {
        try {
            $this->processor->processConfiguration($this->configuration, $config);

            return true;
        } catch (Symfony\InvalidTypeException | Symfony\InvalidConfigurationException $exception) {
            return false;
        }
    }

    /**
     * @throws Configurator\ConfigurationExceptionInterface
     */
    public function compile(array $config): Configurator\RepositoryInterface
    {
        if (array_key_exists('workflow', $config)) {
            return $this->compileWorkflow($config);
        } elseif (array_key_exists('pipeline', $config)) {
            return $this->compilePipeline($config);
        } elseif (array_key_exists('http_hook', $config)) {
            return $this->compileHook($config);
        } elseif (array_key_exists('http_api', $config)) {
            return $this->compileApi($config);
        }

        throw new \LogicException('Not implemented');
    }

    private function compileWorkflow(array $config): Satellite\Builder\Repository\Workflow
    {
        $workflow = new Satellite\Builder\Workflow(
            new Node\Expr\Variable('runtime')
        );

        $repository = new Satellite\Builder\Repository\Workflow($workflow);

        $repository->addPackages(
            'php-etl/workflow:~0.1.0@dev',
        );

        $repository->addFiles(
            new Packaging\File(
                'main.php',
                new Packaging\Asset\InMemory(
                    <<<PHP
                    <?php
    
                    use Kiboko\Component\Runtime\Workflow\WorkflowRuntimeInterface;
                    
                    require __DIR__ . '/vendor/autoload.php';
                    
                    /** @var WorkflowRuntimeInterface \$runtime */
                    \$runtime = require __DIR__ . '/runtime.php';
                    
                    /** @var callable(runtime: WorkflowConsoleRuntime): WorkflowConsoleRuntime \$workflow */
                    \$workflow = require __DIR__ . '/workflow.php';
                    
                    chdir(__DIR__);
                    
                    \$workflow(\$runtime);
                    \$runtime->run();
                    PHP
                )
            )
        );

        $repository->addFiles(
            new Packaging\File(
                'runtime.php',
                new Packaging\Asset\AST(
                    new Node\Stmt\Return_(
                        (new Satellite\Builder\Workflow\WorkflowRuntime())->getNode()
                    )
                )
            )
        );

        foreach ($config['workflow']['jobs'] as $job) {
            if (array_key_exists('pipeline', $job)) {
                $pipeline = $this->compilePipelineJob($job);
                $pipelineFilename = sprintf('%s.php', uniqid('pipeline'));

                $repository->merge($pipeline);
                $repository->addFiles(
                    new Packaging\File(
                        $pipelineFilename,
                        new Packaging\Asset\AST(
                            new Node\Stmt\Return_(
                                (new Satellite\Builder\Workflow\PipelineBuilder($pipeline->getBuilder()))->getNode()
                            )
                        )
                    )
                );

                $workflow->addPipeline($pipelineFilename);
            } else {
                throw new \LogicException('Not implemented');
            }
        }

        return $repository;
    }

    private function compilePipelineJob(array $config): Satellite\Builder\Repository\Pipeline
    {
        $pipeline = new Satellite\Builder\Pipeline(
            new Node\Expr\Variable('runtime'),
        );

        $repository = new Satellite\Builder\Repository\Pipeline($pipeline);

        $interpreter = new Satellite\ExpressionLanguage\ExpressionLanguage();

        $repository->addPackages(
            'php-etl/pipeline-contracts:~0.3.0@dev',
            'php-etl/pipeline:~0.4.0@dev',
            'php-etl/console-state:~0.1.0@dev',
            'php-etl/pipeline-console-runtime:~0.1.0@dev',
            'php-etl/workflow-console-runtime:~0.1.0@dev',
            'psr/log:^1.1',
            'monolog/monolog',
            'symfony/console:^5.2',
            'symfony/dependency-injection:^5.2',
        );

        if (array_key_exists('expression_language', $config['pipeline'])
            && is_array($config['pipeline']['expression_language'])
            && count($config['pipeline']['expression_language'])
        ) {
            foreach ($config['pipeline']['expression_language'] as $provider) {
                $interpreter->registerProvider(new $provider);
            }
        }

        foreach ($config['pipeline']['steps'] as $step) {
            $plugins = array_intersect_key($this->plugins, $step);
            foreach ($plugins as $plugin) {
                $plugin->appendTo($step, $repository);
            }
        }

        return $repository;
    }

    private function compilePipeline(array $config): Satellite\Builder\Repository\Pipeline
    {
        $repository = $this->compilePipelineJob($config);

        $repository->addFiles(
            new Packaging\File(
                'main.php',
                new Packaging\Asset\InMemory(
                    <<<PHP
                    <?php

                    use Kiboko\Component\Runtime\Pipeline\PipelineRuntimeInterface;

                    require __DIR__ . '/vendor/autoload.php';
                    require __DIR__ . '/container.php';

                    /** @var PipelineRuntimeInterface \$runtime */
                    \$runtime = require __DIR__ . '/runtime.php';

                    /** @var callable(runtime: RuntimeInterface): RuntimeInterface \$pipeline */
                    \$pipeline = require __DIR__ . '/pipeline.php';

                    chdir(__DIR__);

                    \$pipeline(\$runtime);
                    \$runtime->run();
                    PHP
                )
            )
        );

        $repository->addFiles(
            new Packaging\File(
                'runtime.php',
                new Packaging\Asset\AST(
                    new Node\Stmt\Return_(
                        (new Satellite\Builder\Pipeline\ConsoleRuntime())->getNode()
                    )
                )
            )
        );

        return $repository;
    }

    private function compileApi(array $config): Satellite\Builder\Repository\API
    {
        $pipeline = new Satellite\Builder\API();

        return new Satellite\Builder\Repository\API($pipeline);
    }

    private function compileHook(array $config): Satellite\Builder\Repository\Hook
    {
        $pipeline = new Satellite\Builder\Hook();

        return new Satellite\Builder\Repository\Hook($pipeline);
    }
}
