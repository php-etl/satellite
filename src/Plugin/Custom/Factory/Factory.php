<?php


namespace Kiboko\Component\Satellite\Plugin\Custom\Factory;

use Kiboko\Component\Packaging;
use Kiboko\Component\Satellite\Plugin\Custom\Factory\Repository\Repository;
use Kiboko\Contract\Configurator;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Kiboko\Component\Satellite\Plugin\Custom\Configuration;
use Symfony\Component\Config\Definition\Exception as Symfony;
use Kiboko\Component\Satellite\Plugin\Custom;
use Symfony\Component\DependencyInjection\ContainerBuilder;


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
        $builder = new Custom\Builder\LoaderBuilder();

        $container = new ContainerBuilder();

        if (array_key_exists('services', $config)) {
            foreach ($config['services'] as $identifier => $service) {
                if (array_key_exists('class', $service)) {
                    $class = $service['class'];
                }

                $definition = $container->register($identifier, $class ?? null);

                if (array_key_exists('arguments', $service)
                    && is_array($service['arguments'])
                    && count($service['arguments']) > 0
                ) {
                    foreach ($service['arguments'] as $key => $argument) {
                        if (is_numeric($key)) {
                            $definition->addArgument($argument);
                        } else {
                            $definition->setArgument($key, $argument);
                        }
                    }
                }

//                if (array_key_exists('calls', $service)
//                    && is_array($service['calls'])
//                    && count($service['calls']) > 0
//                ) {
//                    foreach ($service['calls'] as $key => [$method, $arguments]) {
//                        $definition->addMethodCall($argument);
//                    }
//                }
            }
        }

        $repository = new Repository($builder);

        $repository->addFiles(
            new Packaging\File(
                'container.php',
                new Packaging\Asset\Resource()
            ),
        );

        return $repository;
    }
}
