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
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use function Kiboko\Component\SatelliteToolbox\Configuration\compileValueWhenExpression;

class Loader implements Configurator\FactoryInterface
{
    private Processor $processor;
    private ConfigurationInterface $configuration;

    public function __construct(
        ?ExpressionLanguage $interpreter = null
    ) {
        $this->processor = new Processor();
        $this->configuration = new Configuration();
        $this->interpreter = $interpreter ?? new ExpressionLanguage();
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
        $container = new SatelliteDependencyInjection();

        $builder = new Custom\Builder\Loader();
        $builder->withService(compileValueWhenExpression($this->interpreter, $config['use']));

        $repository = new Repository\Loader($builder);

        $dumper = new PhpDumper($container($config));
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
