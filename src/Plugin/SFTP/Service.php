<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\SFTP;

use Kiboko\Contract\Configurator;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception as Symfony;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use function Kiboko\Component\SatelliteToolbox\Configuration\compileValueWhenExpression;

final class Service implements Configurator\FactoryInterface
{
    private Processor $processor;
    private ConfigurationInterface $configuration;
    private ExpressionLanguage $interpreter;

    public function __construct(?ExpressionLanguage $interpreter = null)
    {
        $this->processor = new Processor();
        $this->configuration = new Configuration();
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
        } catch (Symfony\InvalidTypeException|Symfony\InvalidConfigurationException) {
            return false;
        }
    }

    /**
     * @throws Configurator\ConfigurationExceptionInterface
     */
    public function compile(array $config): Configurator\RepositoryInterface
    {
        if (array_key_exists('expression_language', $config)
            && is_array($config['expression_language'])
            && count($config['expression_language'])
        ) {
            foreach ($config['expression_language'] as $provider) {
                $this->interpreter->registerProvider(new $provider);
            }
        }

        if (array_key_exists('loader', $config)) {
            $loader = new Builder\Loader($this->interpreter);
            if (array_key_exists('servers', $config['loader'])
                && is_array($config['loader']['servers'])
            ) {
                foreach ($config['loader']['servers'] as $server) {
                    if (array_key_exists('port', $server)) {
                        $serverBuilder = new Builder\Server(
                            compileValueWhenExpression($this->interpreter, $server['host']),
                            compileValueWhenExpression($this->interpreter, $server['port']),
                        );
                    } else {
                        $serverBuilder = new Builder\Server(
                            compileValueWhenExpression($this->interpreter, $server['host']),
                        );
                    }
                    if (array_key_exists('base_path', $server)) {
                        $serverBuilder->withBasePath(compileValueWhenExpression($this->interpreter, $server['base_path']));
                    }
                    if (array_key_exists('username', $server)
                        && array_key_exists('password', $server)
                    ) {
                        $serverBuilder->withPasswordAuthentication(
                            compileValueWhenExpression($this->interpreter, $server['username']),
                            compileValueWhenExpression($this->interpreter, $server['password'])
                        );
                    }
                    if (array_key_exists('username', $server)
                        && array_key_exists('public_key', $server)
                        && array_key_exists('private_key', $server)
                    ) {
                        $serverBuilder->withPrivateKeyAuthentication(
                            compileValueWhenExpression($this->interpreter, $server['username']),
                            compileValueWhenExpression($this->interpreter, $server['public_key']),
                            compileValueWhenExpression($this->interpreter, $server['private_key']),
                            array_key_exists('private_key_passphrase', $server) ? compileValueWhenExpression($this->interpreter, $server['private_key_passphrase']) : null,
                        );
                    }
                    $loader->withServer($server, $serverBuilder->getNode());
                }
            }
            if (array_key_exists('put', $config['loader'])
                && is_array($config['loader']['put'])
            ) {
                foreach ($config['loader']['put'] as $put) {
                    $loader->withPut(
                        $put['path'],
                        $put['content'],
                        $put['mode'] ?? null,
                        $put['if'] ?? null,
                    );
                }
            }

            return new Repository($loader);
        }

        throw new \RuntimeException('No suitable build with the provided configuration.');
    }
}
