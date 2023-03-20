<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite;

use Kiboko\Component\Packaging;
use Kiboko\Component\Satellite;
use Kiboko\Contract\Configurator;
use Kiboko\Contract\Configurator\Action;
use Kiboko\Contract\Configurator\PipelineActionInterface;
use PhpParser\Node;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception as Symfony;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

final class Service implements Configurator\FactoryInterface
{
    private readonly Processor $processor;
    private readonly Satellite\Configuration $configuration;
    private readonly ExpressionLanguage $interpreter;
    /** @var array<string, FactoryInterface> */
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

    public function __construct(?ExpressionLanguage $expressionLanguage = null)
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
        foreach ($this->actions as $name => $action) {
            $runtime->addAction($name, $action);
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

    /**
     * @param Action $attribute
     * @param PipelineActionInterface $action
     * @return Service
     */
    private function addAction(
        Configurator\Action $attribute,
        Configurator\PipelineActionInterface $action,
    ): self {
        $this->configuration->addAction($attribute->name, $action->configuration());
        $this->actions[$attribute->name] = $action;

        $this->actionPlugins[$attribute->name] = $applier = new Satellite\Action\ConfigurationApplier($attribute->name, $action, $action->interpreter());
        $applier->withPackages(...$attribute->dependencies);

        $applier->withAction($attribute->name);

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
            } catch (MissingAttributeException) {
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

    public function registerPlugins(Configurator\PipelinePluginInterface|Configurator\PipelineFeatureInterface ...$plugins): self
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

    public function registerActions(Configurator\PipelineActionInterface ...$actions): self
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
        } catch (Symfony\InvalidTypeException|Symfony\InvalidConfigurationException $exception) {
            throw new Configurator\InvalidConfigurationException($exception->getMessage(), 0, $exception);
        }
    }

    public function validate(array $config): bool
    {
        try {
            $this->processor->processConfiguration($this->configuration, $config);

            return true;
        } catch (Symfony\InvalidTypeException|Symfony\InvalidConfigurationException) {
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
            'php-etl/workflow:*',
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
                    
                    /** @var callable(runtime: WorkflowConsoleRuntime): WorkflowConsoleRuntime $workflow */
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

        foreach ($config['workflow']['jobs'] as $job) {
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

                $workflow->addPipeline($pipelineFilename);
            } elseif (array_key_exists('action', $job)) {
                $action = $this->compileActionJob($job);
                $actionFilename = sprintf('%s.php', uniqid('action'));

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

                $workflow->addAction($actionFilename);
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
            'php-etl/pipeline-contracts:0.4.*',
            'php-etl/pipeline:*',
            'php-etl/console-state:*',
            'php-etl/pipeline-console-runtime:*',
            'php-etl/workflow-console-runtime:*',
            'psr/log:*',
            'monolog/monolog:*',
            'symfony/console:^6.0',
            'symfony/dependency-injection:^6.0',
        );

        if (\array_key_exists('expression_language', $config['pipeline'])
            && \is_array($config['pipeline']['expression_language'])
            && \count($config['pipeline']['expression_language'])
        ) {
            foreach ($config['pipeline']['expression_language'] as $provider) {
                $this->interpreter->registerProvider(new $provider());
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
        $pipeline = new Satellite\Builder\API();

        return new Satellite\Builder\Repository\API($pipeline);
    }

    private function compileHook(array $config): Satellite\Builder\Repository\Hook
    {
        $pipeline = new Satellite\Builder\Hook();

        return new Satellite\Builder\Repository\Hook($pipeline);
    }
}
