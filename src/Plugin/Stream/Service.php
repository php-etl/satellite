<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Stream;

use Kiboko\Contract\Configurator;
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
        $this->configuration = new Configuration();
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
        if (array_key_exists('loader', $config)
            && array_key_exists('destination', $config['loader'])
        ) {
            if ($config['loader']['destination'] === 'stderr') {
                return new Repository(new Builder\StderrLoader());
            } elseif ($config['loader']['destination'] === 'stdout') {
                return new Repository(new Builder\StdoutLoader());
            } else if (array_key_exists('format', $config['loader'])
                && $config['loader']['format'] === 'json'
            ) {
                return new Repository(
                    new Builder\JSONStreamLoader(
                        $config['loader']['destination']
                    )
                );
            } else {
                return new Repository(
                    new Builder\DebugLoader(
                        $config['loader']['destination']
                    )
                );
            }
        }

        throw new \RuntimeException('No suitable build with the provided configuration.');
    }
}
