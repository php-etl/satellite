<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\Logger\Factory;

use Kiboko\Component\Satellite\Feature\Logger;
use Kiboko\Contract\Configurator;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception as Symfony;
use Symfony\Component\Config\Definition\Processor;

final readonly class StreamFactory implements Configurator\FactoryInterface
{
    private Processor $processor;
    private ConfigurationInterface $configuration;

    public function __construct()
    {
        $this->processor = new Processor();
        $this->configuration = new Logger\Configuration\StreamConfiguration();
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

    public function compile(array $config): Logger\Factory\Repository\StreamRepository
    {
        $builder = new Logger\Builder\Monolog\StreamBuilder($config['path']);

        if (\array_key_exists('level', $config)) {
            $builder->withLevel($config['level']);
        }

        if (\array_key_exists('file_permissions', $config)) {
            $builder->withFilePermissions($config['file_permissions']);
        }

        if (\array_key_exists('use_locking', $config)) {
            $builder->withLocking($config['use_locking']);
        }

        return new Logger\Factory\Repository\StreamRepository($builder);
    }
}
