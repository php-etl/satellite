<?php


namespace Kiboko\Component\Satellite\Plugin\Custom\Factory;

use Kiboko\Component\Packaging;
use Kiboko\Contract\Configurator;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Kiboko\Component\Satellite\Plugin\Custom\Configuration;
use Symfony\Component\Config\Definition\Exception as Symfony;
use Kiboko\Component\Satellite\Plugin\Custom;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use function Kiboko\Component\SatelliteToolbox\Configuration\compileValueWhenExpression;

class Transformer implements Configurator\FactoryInterface
{
    private Processor $processor;
    private ConfigurationInterface $configuration;

    public function __construct(private ExpressionLanguage $interpreter)
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

    public function compile(array $config): Repository\Transformer
    {
        $builder = new Custom\Builder\Transformer();

        $container = new ContainerBuilder();

        if (array_key_exists('parameters', $config)
            && is_array($config['parameters'])
            && count($config['parameters']) > 0
        ) {
            foreach ($config['parameters'] as $identifier => $parameter) {
                $container->setParameter($identifier, $parameter);
            }
        }

        if (array_key_exists('services', $config)
            && is_array($config['services'])
            && count($config['services']) > 0
        ) {
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
                        if (substr($argument, 0, 1) === '@'
                            && substr($argument, 1, 1) !== '@'
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

        $container->getDefinition($config['use'])->setPublic(true);
        $builder->withService(compileValueWhenExpression($this->interpreter, $config['use']));

        $repository = new Repository\Transformer($builder);

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
