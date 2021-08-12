<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite;

use Kiboko\Component\Satellite;
use Kiboko\Component\SatelliteToolbox;
use Kiboko\Contract\Configurator;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception as Symfony;
use Symfony\Component\Config\Definition\Processor;

final class Service implements Configurator\FactoryInterface
{
    private Processor $processor;
    private ConfigurationInterface $configuration;

    public function __construct(private array $adapters = [], private array $runtimes = [], private array $plugins = [])
    {
        $this->processor = new Processor();
        $this->adapters = array_merge(
            // Core Adapters
            [
                new Adapter\Docker\Configuration(),
                new Adapter\Filesystem\Configuration(),
            ],
            $adapters
        );

        $this->runtimes = array_merge(
            // Core Runtimes
            [
                new Runtime\Api\Configuration(),
                new Runtime\HttpHook\Configuration(),
                new Runtime\Pipeline\Configuration(),
                new Runtime\Workflow\Configuration(),
            ],
            $runtimes
        );

        $this->plugins = array_merge(
            // Core Plugins
            [
                new Plugin\Batching\Service(),
                new Plugin\Custom\Service(),
                new Plugin\FTP\Service(),
                new Plugin\SFTP\Service(),
                new Plugin\Stream\Service(),
            ],
            $plugins
        );

        $this->configuration = (new Satellite\Configuration())
            ->addAdapters(...$this->adapters)
            ->addRuntimes(...$this->runtimes)
            ->addPlugins(...$this->plugins);
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
        $workflow = new Satellite\Builder\Workflow();
        $repository = new Satellite\Builder\Repository\Workflow($workflow);

        foreach ($config['workflow']['jobs'] as $job) {
            if (array_key_exists('pipeline', $job)) {
                $pipeline = $this->compilePipeline($job);

                $repository->merge($pipeline);
                $workflow->addJob($pipeline->getBuilder());
            } else {
                throw new \LogicException('Not implemented');
            }
        }

        return $repository;
    }

    private function compilePipeline(array $config): Satellite\Builder\Repository\Pipeline
    {
        $pipeline = new Satellite\Builder\Pipeline();
        $repository = new Satellite\Builder\Repository\Pipeline($pipeline);

        $interpreter = new Satellite\ExpressionLanguage\ExpressionLanguage();

        $repository->addPackages(
            'php-etl/pipeline:^0.3.0',
            'monolog/monolog',
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

            /** @var  $plugin \Kiboko\Contract\Configurator\FactoryInterface */
            foreach ($this->plugins as $plugin) {
                if (array_key_exists($plugin->configuration()->getName(), $step) && $plugin instanceof Configurator\FactoryInterface) {
                    // TODO - TBD, instead of creating a new instance of the plugin, see how to add the interpreter via a setter for example but it goes against SOLID
                    $configurationApplier = (new Satellite\Pipeline\ConfigurationApplier($plugin->configuration()->getName(), new (get_class($plugin))(clone $interpreter)));

                    if ($plugin instanceof Configurator\ConfiguratorExtractorInterface) {
                        $configurationApplier->withExtractor($plugin->getExtractorKey());
                    }

                    if ($plugin instanceof Configurator\ConfiguratorTransformerInterface) {
                        if (is_null($plugin->getTransformerKeys())) {
                            $configurationApplier->withTransformer(null);
                        } else {
                            foreach ($plugin->getTransformerKeys() as $key) {
                                $configurationApplier->withTransformer($key);
                            }
                        }
                    }

                    if ($plugin instanceof Configurator\ConfiguratorLoaderInterface) {
                        foreach ($plugin->getLoaderKeys() as $key) {
                            $configurationApplier->withLoader($key);
                        }
                    }

                    if ($plugin instanceof Configurator\ConfiguratorPackagesInterface) {
                        $configurationApplier->withPackages(...$plugin->getPackages());
                    }

                    $configurationApplier->appendTo($step, $repository);

                    break;
                }
            }
        }

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
