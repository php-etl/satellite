<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud;

use Kiboko\Component\Satellite;
use Kiboko\Component\Satellite\MissingAttributeException;
use Kiboko\Contract\Configurator;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception as Symfony;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use function Kiboko\Component\Satellite\expectAttributes;

final class Service
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

    public function __construct()
    {
        $this->processor = new Processor();
        $this->configuration = new Satellite\Configuration();
        $this->interpreter = new Satellite\ExpressionLanguage\ExpressionLanguage();

        $this
            ->registerAdapters(
                new \Kiboko\Component\Satellite\Adapter\Docker\Factory(),
                new \Kiboko\Component\Satellite\Adapter\Filesystem\Factory(),
                new \Kiboko\Component\Satellite\Adapter\Tar\Factory(getcwd())
            )
            ->registerRuntimes(
                new Satellite\Runtime\Api\Factory(),
                new Satellite\Runtime\HttpHook\Factory(),
                new Satellite\Runtime\Pipeline\Factory(),
                new Satellite\Runtime\Workflow\Factory(),
            )
            ->registerFactories(
                fn (ExpressionLanguage $interpreter) => new Satellite\Feature\Logger\Service($this->interpreter),
                fn (ExpressionLanguage $interpreter) => new Satellite\Feature\State\Service($this->interpreter),
                fn (ExpressionLanguage $interpreter) => new Satellite\Feature\Rejection\Service($this->interpreter),
                fn (ExpressionLanguage $interpreter) => new Satellite\Plugin\Custom\Service($this->interpreter),
                fn (ExpressionLanguage $interpreter) => new Satellite\Plugin\Stream\Service($this->interpreter),
                fn (ExpressionLanguage $interpreter) => new Satellite\Plugin\SFTP\Service($this->interpreter),
                fn (ExpressionLanguage $interpreter) => new Satellite\Plugin\FTP\Service($this->interpreter),
                fn (ExpressionLanguage $interpreter) => new Satellite\Plugin\Batching\Service($this->interpreter),
                fn (ExpressionLanguage $interpreter) => new \Kiboko\Plugin\Akeneo\Service($this->interpreter),
                fn (ExpressionLanguage $interpreter) => new \Kiboko\Plugin\CSV\Service($this->interpreter),
                fn (ExpressionLanguage $interpreter) => new \Kiboko\Plugin\FastMap\Service($this->interpreter),
                fn (ExpressionLanguage $interpreter) => new \Kiboko\Plugin\Spreadsheet\Service($this->interpreter),
                fn (ExpressionLanguage $interpreter) => new \Kiboko\Plugin\SQL\Service($this->interpreter),
                fn (ExpressionLanguage $interpreter) => new \Kiboko\Plugin\Sylius\Service($this->interpreter)
            )
        ;
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

    /** @param Configurator\FactoryInterface $plugin */
    private function addPipeline(
        Configurator\Pipeline $attribute,
        Configurator\FactoryInterface $plugin,
        ExpressionLanguage $interpreter,
    ): self {
        $this->configuration->addPlugin($attribute->name, $plugin->configuration());
        $this->pipelines[$attribute->name] = $plugin;

        $applier = new Satellite\Pipeline\ConfigurationApplier($attribute->name, $plugin, $interpreter);
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

    public function registerAdapters(Configurator\Adapter\FactoryInterface ...$adapters): self
    {
        foreach ($adapters as $adapter) {
            /* @var Configurator\Adapter $attribute */
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
            foreach (Satellite\expectAttributes($runtime, Configurator\Runtime::class) as $attribute) {
                $this->addRuntime($attribute, $runtime);
            }
        }

        return $this;
    }

    public function registerFactories(callable ...$factories): self
    {
        foreach ($factories as $factory) {
            $plugin = $factory($interpreter = clone $this->interpreter);

            /** @var Configurator\Feature $attribute */
            foreach (Satellite\extractAttributes($plugin, Configurator\Feature::class) as $attribute) {
                $this->addFeature($attribute, $plugin);
            }

            /** @var Configurator\Pipeline $attribute */
            foreach (Satellite\extractAttributes($plugin, Configurator\Pipeline::class) as $attribute) {
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
        } catch (Symfony\InvalidTypeException|Symfony\InvalidConfigurationException $exception) {
            throw new Configurator\InvalidConfigurationException($exception->getMessage(), 0, $exception);
        }
    }

    public function validate(array $config): bool
    {
        try {
            $this->processor->processConfiguration($this->configuration, $config);

            return true;
        } catch (Symfony\InvalidTypeException|Symfony\InvalidConfigurationException $exception) {
            return false;
        }
    }
}
