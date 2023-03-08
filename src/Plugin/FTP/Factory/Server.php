<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\FTP\Factory;

use Kiboko\Component\Satellite\ExpressionLanguage as Satellite;
use Kiboko\Component\Satellite\Plugin\FTP;
use Kiboko\Contract\Configurator;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception as Symfony;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

use function Kiboko\Component\SatelliteToolbox\Configuration\compileValueWhenExpression;

class Server implements Configurator\FactoryInterface
{
    private Processor $processor;
    private ConfigurationInterface $configuration;
    private ExpressionLanguage $interpreter;

    public function __construct(
        ?ExpressionLanguage $interpreter = null
    ) {
        $this->processor = new Processor();
        $this->configuration = new FTP\Configuration();
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

    public function compile(array $config): FTP\Factory\Repository\Repository
    {
        $builder = new FTP\Builder\Server(compileValueWhenExpression($this->interpreter, $config['host']), compileValueWhenExpression($this->interpreter, $config['port']), compileValueWhenExpression($this->interpreter, $config['timeout']));

        if (\array_key_exists('base_path', $config)) {
            $builder->withBasePath(compileValueWhenExpression($this->interpreter, $config['base_path']));
        }

        if (\array_key_exists('username', $config)
            && \array_key_exists('password', $config)
        ) {
            $builder->withPasswordAuthentication(
                compileValueWhenExpression($this->interpreter, $config['username']),
                compileValueWhenExpression($this->interpreter, $config['password'])
            );
        }

        $builder->withPassiveMode(compileValueWhenExpression($this->interpreter, $config['passif_mode']));

        try {
            return new FTP\Factory\Repository\Repository($builder);
        } catch (Symfony\InvalidTypeException|Symfony\InvalidConfigurationException $exception) {
            throw new Configurator\InvalidConfigurationException(message: $exception->getMessage(), previous: $exception);
        }
    }
}
