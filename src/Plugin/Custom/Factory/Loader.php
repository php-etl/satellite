<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Custom\Factory;

use Kiboko\Component\Packaging;
use Kiboko\Component\Satellite\ExpressionLanguage as Satellite;
use Kiboko\Component\Satellite\Plugin\Custom;
use Kiboko\Component\Satellite\Plugin\Custom\Configuration;
use function Kiboko\Component\SatelliteToolbox\Configuration\compileValueWhenExpression;
use Kiboko\Contract\Configurator;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception as Symfony;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class Loader implements Configurator\FactoryInterface
{
    private Processor $processor;
    private ConfigurationInterface $configuration;
    private ExpressionLanguage $interpreter;

    public function __construct(
        ?ExpressionLanguage $interpreter = null
    ) {
        $this->processor = new Processor();
        $this->configuration = new Configuration();
        $this->interpreter = $interpreter ?? new Satellite\ExpressionLanguage();
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
    public function compile(array $config): Repository\Loader
    {
        $builder = new Custom\Builder\Loader(compileValueWhenExpression($this->interpreter, $config['use']));

        $container = new ContainerBuilder();

        if (\array_key_exists('parameters', $config)
            && \is_array($config['parameters'])
            && \count($config['parameters']) > 0
        ) {
            foreach ($config['parameters'] as $identifier => $parameter) {
                $container->setParameter($identifier, $parameter);
            }
        }

        if (\array_key_exists('services', $config)
            && \is_array($config['services'])
            && \count($config['services']) > 0
        ) {
            foreach ($config['services'] as $identifier => $service) {
                if (\array_key_exists('class', $service)) {
                    $class = $service['class'];
                }

                $definition = $container->register($identifier, $class ?? null);

                if (\array_key_exists('arguments', $service)
                    && \is_array($service['arguments'])
                    && \count($service['arguments']) > 0
                ) {
                    foreach ($service['arguments'] as $key => $argument) {
                        if ('@' === substr($argument, 0, 1)
                            && '@' !== substr($argument, 1, 1)
                        ) {
                            $argument = new Reference(substr($argument, 1));
                        }

                        if (is_numeric($key)) {
                            $definition->addArgument($argument);
                        } else {
                            $definition->setArgument($key, $argument);
                        }
                    }
                }

                if (\array_key_exists('calls', $service)
                    && \is_array($service['calls'])
                    && \count($service['calls']) > 0
                ) {
                    foreach ($service['calls'] as $key => [$method, $arguments]) {
                        $definition->addMethodCall($method, array_map(function ($argument) {
                            if (preg_match('/^@[^@]/', $argument)) {
                                return new Reference(substr($argument, 1));
                            }
                            if (preg_match('/^%[^%].*[^%]%$/', $argument)) {
                                return new Parameter(substr($argument, 1, -1));
                            }

                            return $argument;
                        }, $arguments));
                    }
                }

                if (\array_key_exists('factory', $service)
                    && \is_array($service['factory'])
                    && \array_key_exists('class', $service['factory'])
                    && \array_key_exists('method', $service['factory'])
                ) {
                    $definition->setFactory([$service['factory']['class'], $service['factory']['method']]);
                }
            }
        }

        $container->getDefinition($config['use'])->setPublic(true);

        $repository = new Repository\Loader($builder);

        $container->compile();
        $dumper = new PhpDumper($container);
        $repository->addFiles(
            new Packaging\File(
                'container.php',
                new Packaging\Asset\InMemory(
                    $dumper->dump()
                )
            ),
        );

        return $repository;
    }
}
