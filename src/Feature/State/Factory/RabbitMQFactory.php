<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\State\Factory;

use Kiboko\Component\Satellite\Feature\State;
use Kiboko\Contract\Configurator;
use Kiboko\Component\Satellite\Feature\Rejection;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception as Symfony;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use function Kiboko\Component\SatelliteToolbox\Configuration\compileValueWhenExpression;

final class RabbitMQFactory implements Configurator\FactoryInterface
{
    private Processor $processor;
    private ConfigurationInterface $configuration;
    private ExpressionLanguage $interpreter;

    public function __construct(
        ?ExpressionLanguage $interpreter = null,
        private ?string $stepName = null,
        private ?string $stepCode = null
    ) {
        $this->processor = new Processor();
        $this->configuration = new Rejection\Configuration\RabbitMQConfiguration();
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
        } catch (\Exception) {
        }

        return false;
    }

    public function compile(array $config): Repository\RabbitMQRepository
    {
        $builder = new State\Builder\RabbitMQBuilder(
            compileValueWhenExpression($this->interpreter, $config['host']),
            compileValueWhenExpression($this->interpreter, $config['port']),
            compileValueWhenExpression($this->interpreter, $config['vhost']),
            compileValueWhenExpression($this->interpreter, $config['topic']),
        );

        if (array_key_exists('user', $config) && array_key_exists('password', $config)) {
            $builder->withAuthentication(
                compileValueWhenExpression($this->interpreter, $config['user']),
                compileValueWhenExpression($this->interpreter, $config['password']),
            );
        }

        if (array_key_exists('line_threshold', $config)) {
            $builder->withLineThreshold(
                compileValueWhenExpression($this->interpreter, $config['line_threshold'])
            );
        }

        if (array_key_exists('exchange', $config)) {
            $builder->withExchange(
                compileValueWhenExpression($this->interpreter, $config['exchange'])
            );
        }

        return new Repository\RabbitMQRepository($builder);
    }
}
