<?php

namespace Kiboko\Component\Satellite\Plugin\FTP\Factory;

use Kiboko\Component\Satellite\ExpressionLanguage\ExpressionLanguage;
use Kiboko\Contract\Configurator;
use Symfony\Component\Config\Definition\Exception as Symfony;
use Kiboko\Contract\Configurator\RepositoryInterface;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;
use Kiboko\Component\Satellite\Plugin\FTP;
use function Kiboko\Component\SatelliteToolbox\Configuration\compileValueWhenExpression;

class Loader implements Configurator\FactoryInterface
{
    private Processor $processor;
    private ConfigurationInterface $configuration;

    public function __construct(private ExpressionLanguage $interpreter)
    {
        $this->processor = new Processor();
        $this->configuration = new FTP\Configuration();
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

    public function compile(array $config): RepositoryInterface
    {
        $builder = new FTP\Builder\Loader($this->interpreter);

        if (array_key_exists('servers', $config['loader'])
            && is_array($config['loader']['servers'])
        ) {
            foreach ($config['loader']['servers'] as $server) {
                $serverFactory = new FTP\Factory\Server($this->interpreter);
                $builder->addServerBasePath($server['host'], compileValueWhenExpression($this->interpreter, $server['base_path']));

                $loader = $serverFactory->compile($server);
                $serverBuilder = $loader->getBuilder();

                $builder->withServer($server, $serverBuilder->getNode());
            }
        }

        if (array_key_exists('put', $config['loader'])
            && is_array($config['loader']['put'])
        ) {
            foreach ($config['loader']['put'] as $put) {
                $builder->withPut(
                    compileValueWhenExpression($this->interpreter, $put["path"]),
                    compileValueWhenExpression($this->interpreter, $put["content"]),
                    array_key_exists('mode', $put) ? compileValueWhenExpression($this->interpreter, $put["mode"]) : null,
                    array_key_exists('if', $put) ? compileValueWhenExpression($this->interpreter, $put["if"]) : null,
                );
            }
        }

        try {
            return new FTP\Factory\Repository\Repository($builder);
        } catch (Symfony\InvalidTypeException|Symfony\InvalidConfigurationException $exception) {
            throw new Configurator\InvalidConfigurationException(
                message: $exception->getMessage(),
                previous: $exception
            );
        }
    }
}
