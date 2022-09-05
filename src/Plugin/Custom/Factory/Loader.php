<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Custom\Factory;

use Kiboko\Component\Packaging;
use Kiboko\Component\Satellite\DependencyInjection\SatelliteDependencyInjection;
use Kiboko\Component\Satellite\ExpressionLanguage as Satellite;
use Kiboko\Component\Satellite\Plugin\Custom;
use Kiboko\Component\Satellite\Plugin\Custom\Configuration;
use function Kiboko\Component\SatelliteToolbox\Configuration\compileValueWhenExpression;
use Kiboko\Contract\Configurator;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception as Symfony;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\String\ByteString;

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
        $containerName = sprintf('ProjectServiceContainer%s', ByteString::fromRandom(8)->toString());

        $builder = new Custom\Builder\Loader(compileValueWhenExpression($this->interpreter, $config['use']), $containerName);

        $container = (new SatelliteDependencyInjection())($config);

        $repository = new Repository\Loader($builder);

        $dumper = new PhpDumper($container);
        $repository->addFiles(
            new Packaging\File(
                sprintf('%s.php', $containerName),
                new Packaging\Asset\InMemory(
                    $dumper->dump(['class' => $containerName, 'namespace' => 'GyroscopsGenerated'])
                )
            ),
        );

        return $repository;
    }
}
