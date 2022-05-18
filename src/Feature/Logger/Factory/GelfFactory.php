<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\Logger\Factory;

use Kiboko\Component\Satellite\Feature\Logger;
use Kiboko\Contract\Configurator;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception as Symfony;
use Symfony\Component\Config\Definition\Processor;

final class GelfFactory implements Configurator\FactoryInterface
{
    private Processor $processor;
    private ConfigurationInterface $configuration;

    public function __construct()
    {
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
        } catch (Symfony\InvalidTypeException|Symfony\InvalidConfigurationException $exception) {
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

    public function compile(array $config): Repository\GelfRepository
    {
        $builder = new Logger\Builder\Monolog\GelfBuilder();

        if (\array_key_exists('level', $config)) {
            $builder->withLevel($config['level']);
        }

        if (\array_key_exists('tcp', $config)) {
            $builder->withTCPTransport(
                $config['tcp']['host'] ?? null,
                $config['tcp']['port'] ?? null,
            );
        } elseif (\array_key_exists('amqp', $config)) {
            $builder->withAMQPTransport(
                $config['amqp']['queue'] ?? null,
                $config['amqp']['channel'] ?? null,
                $config['amqp']['vhost'] ?? null,
                $config['amqp']['host'] ?? null,
                $config['amqp']['port'] ?? null,
                $config['amqp']['timeout'] ?? null,
            );
        }

        return new Repository\GelfRepository($builder);
    }
}
