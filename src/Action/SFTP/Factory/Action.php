<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Action\SFTP\Factory;

use Kiboko\Component\Satellite\ExpressionLanguage as Satellite;
use Kiboko\Component\Satellite\Action\SFTP;
use Kiboko\Component\Satellite\Action\SFTP\Factory\Repository\Repository;
use function Kiboko\Component\SatelliteToolbox\Configuration\compileValueWhenExpression;
use Kiboko\Contract\Configurator;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception as Symfony;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class Action implements Configurator\FactoryInterface
{
    private Processor $processor;
    private ConfigurationInterface $configuration;
    private ExpressionLanguage $interpreter;

    public function __construct(
        ?ExpressionLanguage $interpreter = null
    ) {
        $this->processor = new Processor();
        $this->configuration = new SFTP\Configuration();
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
            $this->normalize($config);

            return true;
        } catch (Symfony\InvalidTypeException|Symfony\InvalidConfigurationException $exception) {
            return false;
        }
    }

    public function compile(array $config): Repository
    {
        $builder = new SFTP\Builder\Action($this->interpreter);

        if (\array_key_exists('server', $config['execution'])) {
            $server = $config['execution']['server'];

            $serverFactory = new Server($this->interpreter);

            $loader = $serverFactory->compile($server);
            $serverBuilder = $loader->getBuilder();

            $builder->withServer($server, $serverBuilder->getNode());
        }

        if (\array_key_exists('put', $config['execution'])) {
            $destination = $config['execution']['put'];

            $builder->withPut(
                compileValueWhenExpression($this->interpreter, $destination['path']),
                compileValueWhenExpression($this->interpreter, $destination['content']),
                \array_key_exists('mode', $destination) ? compileValueWhenExpression($this->interpreter, $destination['mode']) : null,
                \array_key_exists('if', $destination) ? compileValueWhenExpression($this->interpreter, $destination['if']) : null,
            );
        }

        try {
            return new Repository($builder);
        } catch (Symfony\InvalidTypeException|Symfony\InvalidConfigurationException $exception) {
            throw new Configurator\InvalidConfigurationException(message: $exception->getMessage(), previous: $exception);
        }
    }
}
