<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite;

use Kiboko\Component\Packaging;
use Kiboko\Component\Satellite;
use Kiboko\Component\Satellite\DependencyInjection\SatelliteDependencyInjection;
use Kiboko\Contract\Configurator;
use PhpParser\Node;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception as Symfony;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

final class Service implements Configurator\FactoryInterface
{
    private readonly Processor $processor;
    private readonly Satellite\Configuration $configuration;
    private readonly ExpressionLanguage $interpreter;
    /** @var array<string, Configurator\Adapter\FactoryInterface> */
    private array $adapters = [];
    /** @var array<string, Satellite\Runtime\FactoryInterface> */
    private array $runtimes = [];
    /** @var array<string, Configurator\FactoryInterface> */
    private array $features = [];
    /** @var array<string, Configurator\FactoryInterface> */
    private array $pipelines = [];
    /** @var array<string, Satellite\Pipeline\ConfigurationApplier> */
    private array $plugins = [];
    /** @var array<string, Configurator\FactoryInterface> */
    private array $actions = [];
    /** @var array<string, Satellite\Action\ConfigurationApplier> */
    private array $actionPlugins = [];

    public function __construct(ExpressionLanguage $expressionLanguage = null)
    {
        $this->processor = new Processor();
        $this->configuration = new Satellite\Configuration();
        $this->interpreter = $expressionLanguage ?? new Satellite\ExpressionLanguage\ExpressionLanguage();
    }

    public function adapterChoice(): Satellite\Adapter\AdapterChoice
    {
        return new Satellite\Adapter\AdapterChoice($this->adapters);
    }

    private function addAdapter(Configurator\Adapter $attribute, Configurator\Adapter\FactoryInterface $adapter): self
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

    private function addPipelinePlugin(
        Configurator\Pipeline $attribute,
        Configurator\PipelinePluginInterface $plugin,
    ): self {
        $this->configuration->addPlugin($attribute->name, $plugin->configuration());
        $this->pipelines[$attribute->name] = $plugin;

        $this->plugins[$attribute->name] = $applier = new Satellite\Pipeline\ConfigurationApplier($attribute->name, $plugin, $plugin->interpreter());
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

    private function addAction(
        Configurator\Action $attribute,
        Configurator\ActionInterface $action,
    ): self {
        $this->configuration->addAction($attribute->name, $action->configuration());
        $this->actions[$attribute->name] = $action;

        $this->actionPlugins[$attribute->name] = $applier = new Satellite\Action\ConfigurationApplier($attribute->name, $action, $action->interpreter());
        $applier->withPackages(...$attribute->dependencies);

        $applier->withAction();

        return $this;
    }

    public function registerAdapters(Configurator\Adapter\FactoryInterface ...$adapters): self
    {
        foreach ($adapters as $adapter) {
            /* @var Configurator\Adapter $attribute */
            try {
                foreach (expectAttributes($adapter, Configurator\Adapter::class) as $attribute) {
                    $this->addAdapter($attribute, $adapter);
                }
            } catch (Satellite\MissingAttributeException) {
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

    public function registerPlugins(Configurator\PipelineFeatureInterface|Configurator\PipelinePluginInterface ...$plugins): self
    {
        foreach ($plugins as $plugin) {
            /** @var Configurator\Feature $attribute */
            foreach (extractAttributes($plugin, Configurator\Feature::class) as $attribute) {
                $this->addFeature($attribute, $plugin);
            }

            /** @var Configurator\Pipeline $attribute */
            foreach (extractAttributes($plugin, Configurator\Pipeline::class) as $attribute) {
                $this->addPipelinePlugin($attribute, $plugin);
            }
        }

        return $this;
    }

    public function registerActions(Configurator\ActionInterface ...$actions): self
    {
        foreach ($actions as $action) {
            /** @var Configurator\Action $attribute */
            foreach (extractAttributes($action, Configurator\Action::class) as $attribute) {
                $this->addAction($attribute, $action);
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
        } catch (Symfony\InvalidConfigurationException|Symfony\InvalidTypeException $exception) {
            throw new Configurator\InvalidConfigurationException($exception->getMessage(), 0, $exception);
        }
    }

    public function validate(array $config): bool
    {
        try {
            $this->processor->processConfiguration($this->configuration, $config);

            return true;
        } catch (Symfony\InvalidConfigurationException|Symfony\InvalidTypeException) {
            return false;
        }
    }

    /**
     * @throws Configurator\ConfigurationExceptionInterface
     */
    public function compile(array $config): Configurator\RepositoryInterface
    {
        if (\array_key_exists('workflow', $config)) {
            return $this->compileWorkflow($config);
        }
        if (\array_key_exists('pipeline', $config)) {
            return $this->compilePipeline($config);
        }
        if (\array_key_exists('http_hook', $config)) {
            return $this->compileHook($config);
        }
        if (\array_key_exists('http_api', $config)) {
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
            'php-etl/satellite-contracts:>=0.1.1 <0.2',
            'php-etl/pipeline-contracts:>=0.5.1 <0.6',
            'php-etl/action-contracts:>=0.2.0 <0.3',
            'php-etl/workflow:*',
            'php-etl/workflow-console-runtime:*',
            'psr/log:*',
            'monolog/monolog:*',
            'symfony/dotenv:^6.0'
        );

        $repository->addFiles(
            new Packaging\File(
                'main.php',
                new Packaging\Asset\InMemory(<<<'PHP'
                    <?php

                    use Kiboko\Component\Runtime\Workflow\WorkflowRuntimeInterface;

                    require __DIR__ . '/vendor/autoload.php';

                    /** @var WorkflowRuntimeInterface $runtime */
                    $runtime = require __DIR__ . '/runtime.php';
                    
                    /** @var callable(runtime: WorkflowRuntimeInterface): WorkflowRuntimeInterface $workflow */
                    $workflow = require __DIR__ . '/workflow.php';
                    
                    chdir(__DIR__);
                    
                    $workflow($runtime);
                    $runtime->run();
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

        foreach ($config['workflow']['jobs'] as $code => $job) {
            if (\array_key_exists('pipeline', $job)) {
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

                $workflow->addPipeline($code, $pipelineFilename);
            } elseif (\array_key_exists('action', $job)) {
                $action = $this->compileActionJob($job);
                $actionFilename = sprintf('%s.php', uniqid('action'));

                $repository->merge($action);
                $repository->addFiles(
                    new Packaging\File(
                        $actionFilename,
                        new Packaging\Asset\AST(
                            new Node\Stmt\Return_(
                                (new Satellite\Builder\Workflow\ActionBuilder($action->getBuilder()))->getNode()
                            )
                        )
                    )
                );

                $workflow->addAction($code, $actionFilename);
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

        $repository->addPackages(
            'php-etl/satellite-contracts:>=0.1.1 <0.2',
            'php-etl/pipeline-contracts:>=0.5.1 <0.6',
            'php-etl/pipeline:*',
            'php-etl/pipeline-console-runtime:*',
            'psr/log:*',
            'monolog/monolog:*',
            'symfony/dotenv:^6.0'
        );

        if (\array_key_exists('expression_language', $config['pipeline'])
            && \is_array($config['pipeline']['expression_language'])
            && \count($config['pipeline']['expression_language'])
        ) {
            foreach ($config['pipeline']['expression_language'] as $provider) {
                $this->interpreter->registerProvider(new $provider());
            }
        }

        foreach ($config['pipeline']['steps'] as $code => $step) {
            $step['code'] = $code;

            $plugins = array_intersect_key($this->plugins, $step);
            foreach ($plugins as $plugin) {
                $plugin->appendTo($step, $repository);
            }
        }

        return $repository;
    }

    private function compileActionJob(array $config): Satellite\Builder\Repository\Action
    {
        $action = new Satellite\Builder\Action(
            new Node\Expr\Variable('runtime'),
        );

        $repository = new Satellite\Builder\Repository\Action($action);

        $actions = array_intersect_key($this->actionPlugins, $config['action']);
        foreach ($actions as $action) {
            $action->appendTo($config['action'], $repository);
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
                    <<<'PHP'
                        <?php

                        use Kiboko\Component\Runtime\Pipeline\PipelineRuntimeInterface;

                        require __DIR__ . '/vendor/autoload.php';

                        /** @var PipelineRuntimeInterface $runtime */
                        $runtime = require __DIR__ . '/runtime.php';

                        /** @var callable(runtime: RuntimeInterface): RuntimeInterface $pipeline */
                        $pipeline = require __DIR__ . '/pipeline.php';

                        chdir(__DIR__);

                        $pipeline($runtime);
                        $runtime->run();
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
        $apiBuilder = new Satellite\Builder\API();

        $repository = new Satellite\Builder\Repository\API($apiBuilder);

        $pipelineMapping = [];

        foreach ($config['http_api']['routes'] as $route) {
            if (\array_key_exists('pipeline', $route)) {
                $pipeline = $this->compilePipelineJob($route);
                $pipelineFilename = sprintf('%s.php', uniqid('pipeline', true));
                $pipelineMapping[$route['route']] = $pipelineFilename;

                $repository->merge($pipeline);
                $repository->addFiles(
                    new Packaging\File(
                        $pipelineFilename,
                        new Packaging\Asset\AST(
                            new Node\Stmt\Return_(
                                (new Satellite\Builder\API\PipelineBuilder($pipeline->getBuilder()))->getNode()
                            )
                        )
                    )
                );
            } else {
                throw new \LogicException('Not implemented');
            }
        }

        $compiledMapping = '';
        foreach ($pipelineMapping as $route => $pipeline) {
            $compiledMapping .= \PHP_EOL.<<<PHP
                \$pipeline = require '{$pipeline}';
                \$hook = require 'hook.php';
                \$pipeline(\$hook);
                \$runtime->addHookRuntime('{$route}',\$hook);
                PHP;
        }

        $repository->addFiles(
            new Packaging\File(
                'main.php',
                new Packaging\Asset\InMemory(
                    <<<'PHP'
                        <?php

                        use Kiboko\Component\Runtime\Api\APIRuntime;

                        require __DIR__ . '/vendor/autoload.php';
                        require __DIR__ . '/container.php';

                        /** @var APIRuntime $runtime */
                        $runtime = require __DIR__ . '/runtime.php';
                        PHP.
                    $compiledMapping.\PHP_EOL
                    .
                    <<<'PHP'
                        /** @var callable(runtime: RuntimeInterface): RuntimeInterface $api */
                        $api = require __DIR__ . '/api.php';

                        $api($runtime);
                        PHP
                )
            )
        );

        $repository->addFiles(
            new Packaging\File(
                'hook.php',
                new Packaging\Asset\AST(
                    new Node\Stmt\Return_(
                        (new Satellite\Builder\Hook\HookRuntime())->getNode()
                    )
                )
            )
        );

        $repository->addFiles(
            new Packaging\File(
                'runtime.php',
                new Packaging\Asset\AST(
                    new Node\Stmt\Return_(
                        (new Satellite\Builder\API\APIRuntime())->getNode()
                    )
                )
            )
        );

        $container = new SatelliteDependencyInjection();

        $dumper = new PhpDumper($container($config['http_api']));
        $repository->addFiles(
            new Packaging\File(
                'container.php',
                new Packaging\Asset\InMemory(
                    $dumper->dump()
                )
            ),
        );

        return $repository;
    }

    private function compileHook(array $config): Satellite\Builder\Repository\Hook
    {
        $hookBuilder = new Satellite\Builder\Hook();

        $repository = new Satellite\Builder\Repository\Hook($hookBuilder);

        $repository->addFiles(
            new Packaging\File(
                'main.php',
                new Packaging\Asset\InMemory(
                    <<<'PHP'
                        <?php

                        use Kiboko\Component\Runtime\Hook\HookRuntime;

                        require __DIR__ . '/vendor/autoload.php';
                        require __DIR__ . '/container.php';

                        /** @var HookRuntime $runtime */
                        $runtime = require __DIR__ . '/runtime.php';

                        /** @var callable(runtime: RuntimeInterface): RuntimeInterface $pipeline */
                        $pipeline = require __DIR__ . '/pipeline.php';

                        $hook = require __DIR__ . '/hook.php';

                        $pipeline($runtime);
                        $hook($runtime);
                        $runtime->run();
                        PHP
                )
            )
        );

        $repository->addFiles(
            new Packaging\File(
                'runtime.php',
                new Packaging\Asset\AST(
                    new Node\Stmt\Return_(
                        (new Satellite\Builder\Hook\HookRuntime())->getNode()
                    )
                )
            )
        );

        $container = new SatelliteDependencyInjection();

        $dumper = new PhpDumper($container($config));
        $repository->addFiles(
            new Packaging\File(
                'container.php',
                new Packaging\Asset\InMemory(
                    $dumper->dump()
                )
            ),
        );

        if (\array_key_exists('pipeline', $config['http_hook'])) {
            $pipeline = $this->compilePipelineJob($config['http_hook']);
            $repository->merge($pipeline);
            $pipelineFilename = 'pipeline.php';

            $repository->addFiles(
                new Packaging\File(
                    $pipelineFilename,
                    new Packaging\Asset\AST(
                        new Node\Stmt\Return_(
                            (new Satellite\Builder\Hook\PipelineBuilder($pipeline->getBuilder()))->getNode()
                        )
                    )
                )
            );
        } else {
            throw new \LogicException('Not implemented');
        }

        return $repository;
    }
}
