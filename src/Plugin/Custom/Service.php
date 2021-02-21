<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Custom;

use Kiboko\Component\Satellite;
use Kiboko\Contract\Configurator;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception as Symfony;
use Symfony\Component\Config\Definition\Processor;

final class Service implements Configurator\FactoryInterface
{
    private Processor $processor;
    private ConfigurationInterface $configuration;

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
        if (array_key_exists('extractor', $config) && array_key_exists('class', $config['extractor'])) {
            return new Repository(new CustomBuilder($config['extractor']['class']));
        }
        if (array_key_exists('transformer', $config) && array_key_exists('class', $config['transformer'])) {
            return new Repository(new CustomBuilder($config['transformer']['class']));
        }
        if (array_key_exists('loader', $config) && array_key_exists('class', $config['loader'])) {
            return new Repository(new CustomBuilder($config['loader']['class']));
        }

        throw new \RuntimeException('No possible pipeline step, expecing "extractor", "transformer" or "loader"');
    }
}
