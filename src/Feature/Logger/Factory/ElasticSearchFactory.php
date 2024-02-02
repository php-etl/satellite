<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\Logger\Factory;

use Kiboko\Component\Satellite\ExpressionLanguage as Satellite;
use Kiboko\Component\Satellite\Feature\Logger;
use Kiboko\Contract\Configurator;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception as Symfony;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

final readonly class ElasticSearchFactory implements Configurator\FactoryInterface
{
    private Processor $processor;
    private ConfigurationInterface $configuration;

    public function __construct(
        private ExpressionLanguage $interpreter = new Satellite\ExpressionLanguage()
    ) {
        $this->processor = new Processor();
        $this->configuration = new Logger\Configuration\GelfConfiguration();
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
        } catch (\Exception) {
        }

        return false;
    }

    public function compile(array $config): Logger\Factory\Repository\ElasticSearchRepository
    {
        $builder = new Logger\Builder\Monolog\ElasticSearchBuilder($this->interpreter);

        if (\array_key_exists('level', $config)) {
            $builder->withLevel($config['level']);
        }

        if (\array_key_exists('hosts', $config)) {
            $builder->withHosts(...$config['hosts']);
        }

        if (\array_key_exists('index', $config)) {
            $builder->withIndex($config['index']);
        }

        return new Logger\Factory\Repository\ElasticSearchRepository($builder);
    }
}
