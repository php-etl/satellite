<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\State\Factory;

use Kiboko\Component\Satellite\ExpressionLanguage as Satellite;
use Kiboko\Component\Satellite\Feature\State;
use Kiboko\Contract\Configurator;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception as Symfony;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

use function Kiboko\Component\SatelliteToolbox\Configuration\compileValueWhenExpression;

final readonly class RabbitMQFactory implements Configurator\FactoryInterface
{
    private Processor $processor;
    private ConfigurationInterface $configuration;

    public function __construct(
        private ExpressionLanguage $interpreter = new Satellite\ExpressionLanguage(),
    ) {
        $this->processor = new Processor();
        $this->configuration = new State\Configuration\RabbitMQConfiguration();
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
        } catch (\Exception) {
        }

        return false;
    }

    public function compile(array $config): Repository\RabbitMQRepository
    {
        $builder = new State\Builder\RabbitMQBuilder(
            stepCode: compileValueWhenExpression($this->interpreter, uniqid()),
            stepLabel: compileValueWhenExpression($this->interpreter, uniqid()),
            topic: compileValueWhenExpression($this->interpreter, $config['topic']),
        );

        if (\array_key_exists('exchange', $config)) {
            $builder->withExchange(compileValueWhenExpression($this->interpreter, $config['exchange']));
        }

        return new Repository\RabbitMQRepository($builder);
    }
}
