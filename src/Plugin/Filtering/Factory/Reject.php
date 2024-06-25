<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Filtering\Factory;

use Kiboko\Component\Satellite\ExpressionLanguage as Satellite;
use Kiboko\Component\Satellite\Plugin\Filtering;
use Kiboko\Component\Satellite\Plugin\Filtering\Configuration;
use Kiboko\Contract\Configurator;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception as Symfony;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

use function Kiboko\Component\SatelliteToolbox\Configuration\compileExpression;
use function Kiboko\Component\SatelliteToolbox\Configuration\compileValueWhenExpression;

class Reject implements Configurator\FactoryInterface
{
    private readonly Processor $processor;
    private readonly ConfigurationInterface $configuration;

    public function __construct(
        private readonly ExpressionLanguage $interpreter = new Satellite\ExpressionLanguage(),
        private readonly array $providers = [],
    ) {
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
        } catch (Symfony\InvalidConfigurationException|Symfony\InvalidTypeException $exception) {
            throw new Configurator\InvalidConfigurationException($exception->getMessage(), 0, $exception);
        }
    }

    public function validate(array $config): bool
    {
        try {
            $this->processor->processConfiguration($this->configuration, $config);

            return true;
        } catch (Symfony\InvalidConfigurationException|Symfony\InvalidTypeException) {
            return false;
        }
    }

    /**
     * @throws Configurator\ConfigurationExceptionInterface
     */
    public function compile(array $config): Repository\Reject
    {
        $interpreter = clone $this->interpreter;

        $builder = new Filtering\Builder\Reject();

        $repository = new Repository\Reject($builder);

        $exclusionBuilder = new Filtering\Builder\ExclusionsBuilder();
        foreach ($config as $condition) {
            $exclusionBuilder
                ->withCondition(
                    compileExpression($interpreter, $condition['when']),
                    compileValueWhenExpression($interpreter, $condition['reason']),
                )
            ;
        }

        $builder->withExclusions(...$exclusionBuilder->build());

        return $repository;
    }
}
