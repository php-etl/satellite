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

class Extractor implements Configurator\FactoryInterface
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

    public function compile(array $config): Repository\Extractor
    {
        $builder = new Custom\Builder\Extractor();
        $builder->withService(compileValueWhenExpression($this->interpreter, $config['use']));

        return new Repository\Extractor($builder);
    }
}
