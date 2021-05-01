<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite;

use Kiboko\Component\Satellite;
use Kiboko\Contract\Configurator;
use Kiboko\Plugin\CSV;
use Kiboko\Plugin\Akeneo;
use Kiboko\Plugin\Sylius;
use Kiboko\Plugin\FastMap;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception as Symfony;
use Symfony\Component\Config\Definition\Processor;

final class Service implements Configurator\FactoryInterface
{
    private Processor $processor;
    private ConfigurationInterface $configuration;

    public function __construct()
    {
        $this->processor = new Processor();
        $this->configuration = (new Configuration())
            ->addAdapters(
                new Adapter\Docker\Configuration(),
                new Adapter\Filesystem\Configuration(),
            )
            ->addRuntimes(
                new Runtime\Api\Configuration(),
                new Runtime\HttpHook\Configuration(),
                new Runtime\Pipeline\Configuration(),
                new Runtime\Workflow\Configuration(),
            );
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

        $repository->addPackages('php-etl/pipeline:^0.2');

        foreach ($config['pipeline']['steps'] as $step) {
            if (array_key_exists('akeneo', $step)) {
                (new Satellite\Pipeline\ConfigurationApplier('akeneo', new Akeneo\Service()))
                    ->withPackages('akeneo/api-php-client-ee')
                    ->withExtractor()
                    ->withLoader()
                    ->appendTo($step, $repository);
            } elseif (array_key_exists('sylius', $step)) {
                (new Satellite\Pipeline\ConfigurationApplier('sylius', new Sylius\Service()))
                    ->withPackages('diglin/sylius-api-php-client')
                    ->withExtractor()
                    ->withLoader()
                    ->appendTo($step, $repository);
            } elseif (array_key_exists('csv', $step)) {
                (new Satellite\Pipeline\ConfigurationApplier('csv', new CSV\Service()))
                    ->withPackages('php-etl/csv-flow:^0.1')
                    ->withExtractor()
                    ->withLoader()
                    ->appendTo($step, $repository);
            } elseif (array_key_exists('custom', $step)) {
                (new Satellite\Pipeline\ConfigurationApplier('custom', new Satellite\Plugin\Custom\Service()))
                    ->withExtractor()
                    ->withTransformer()
                    ->withLoader()
                    ->appendTo($step, $repository);
            } elseif (array_key_exists('stream', $step)) {
                (new Satellite\Pipeline\ConfigurationApplier('stream', new Satellite\Plugin\Stream\Service()))
                    ->withLoader()
                    ->appendTo($step, $repository);
            } elseif (array_key_exists('fastmap', $step)) {
                (new Satellite\Pipeline\ConfigurationApplier('fastmap', new FastMap\Service()))
                    ->withPackages('php-etl/fast-map:^0.2')
                    ->withTransformer(null)
                    ->appendTo($step, $repository);
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
