<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Action\SFTP\Factory;

use Kiboko\Component\Satellite\Action\SFTP;
use Kiboko\Component\Satellite\Action\SFTP\Factory\Repository\Repository;
use Kiboko\Component\Satellite\ExpressionLanguage as Satellite;
use Kiboko\Contract\Configurator;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception as Symfony;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

use function Kiboko\Component\SatelliteToolbox\Configuration\compileValueWhenExpression;

class Action implements Configurator\FactoryInterface
{
    private readonly Processor $processor;
    private readonly ConfigurationInterface $configuration;

    public function __construct(
        private readonly ExpressionLanguage $interpreter = new Satellite\ExpressionLanguage()
    ) {
        $this->processor = new Processor();
        $this->configuration = new SFTP\Configuration();
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
            $this->normalize($config);

            return true;
        } catch (Symfony\InvalidConfigurationException|Symfony\InvalidTypeException) {
            return false;
        }
    }

    public function compile(array $config): Repository
    {
        $builder = new SFTP\Builder\Action(
            compileValueWhenExpression($this->interpreter, $config['server']['host']),
            compileValueWhenExpression($this->interpreter, $config['server']['port']),
            compileValueWhenExpression($this->interpreter, $config['server']['username']),
            compileValueWhenExpression($this->interpreter, $config['server']['password']),
            compileValueWhenExpression($this->interpreter, $config['file']),
            compileValueWhenExpression($this->interpreter, $config['destination']['path']),
        );

        try {
            return new Repository($builder);
        } catch (Symfony\InvalidConfigurationException|Symfony\InvalidTypeException $exception) {
            throw new Configurator\InvalidConfigurationException(message: $exception->getMessage(), previous: $exception);
        }
    }
}
