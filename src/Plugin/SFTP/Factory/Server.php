<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\SFTP\Factory;

use Kiboko\Component\Satellite\ExpressionLanguage as Satellite;
use Kiboko\Component\Satellite\Plugin\SFTP;
use Kiboko\Contract\Configurator;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception as Symfony;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

use function Kiboko\Component\SatelliteToolbox\Configuration\compileValueWhenExpression;

class Server implements Configurator\FactoryInterface
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
        } catch (Symfony\InvalidTypeException|Symfony\InvalidConfigurationException $exception) {
            throw new Configurator\InvalidConfigurationException($exception->getMessage(), 0, $exception);
        }
    }

    public function validate(array $config): bool
    {
        try {
            $this->normalize($config);

            return true;
        } catch (Symfony\InvalidTypeException|Symfony\InvalidConfigurationException) {
            return false;
        }
    }

    public function compile(array $config): SFTP\Factory\Repository\Repository
    {
        $builder = new SFTP\Builder\Server(compileValueWhenExpression($this->interpreter, $config['host']), compileValueWhenExpression($this->interpreter, $config['port']));

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
        if (\array_key_exists('username', $config)
            && \array_key_exists('public_key', $config)
            && \array_key_exists('private_key', $config)
        ) {
            $builder->withPrivateKeyAuthentication(
                compileValueWhenExpression($this->interpreter, $config['username']),
                compileValueWhenExpression($this->interpreter, $config['public_key']),
                compileValueWhenExpression($this->interpreter, $config['private_key']),
                \array_key_exists('private_key_passphrase', $config) ? compileValueWhenExpression($this->interpreter, $config['private_key_passphrase']) : null,
            );
        }

        try {
            return new SFTP\Factory\Repository\Repository($builder);
        } catch (Symfony\InvalidTypeException|Symfony\InvalidConfigurationException $exception) {
            throw new Configurator\InvalidConfigurationException(message: $exception->getMessage(), previous: $exception);
        }
    }
}
