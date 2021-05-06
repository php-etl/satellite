<?php


namespace Kiboko\Component\Satellite\Plugin\Custom\Factory;

use Kiboko\Component\Satellite\Plugin\Custom\Factory\Repository\Repository;
use Kiboko\Contract\Configurator;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Kiboko\Component\Satellite\Plugin\Custom\Configuration;
use Symfony\Component\Config\Definition\Exception as Symfony;
use Kiboko\Component\Satellite\Plugin\Custom;


class Factory implements Configurator\FactoryInterface
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
            if ($this->normalize($config)) {
                return true;
            }
        } catch (\Exception $exception) {
            throw new Configurator\InvalidConfigurationException($exception->getMessage(), 0, $exception);
        }
        return false;
    }
    public function compile(array $config): Repository
    {
        $builder = new Custom\Builder\CustomBuilder(
            $config['services'],
            $config['use'],
            $config['parameters'],

        );

        return new Repository($builder);
    }
}