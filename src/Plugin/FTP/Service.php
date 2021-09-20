<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\FTP;

use Kiboko\Component\Satellite\Plugin\FTP\Factory\Repository\Repository;
use Kiboko\Contract\Configurator;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception as Symfony;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

#[Configurator\PipelineStepLoader]
final class Service implements Configurator\FactoryInterface
{
    private Processor $processor;
    private ConfigurationInterface $configuration;
    private ExpressionLanguage $interpreter;

    public function __construct(
        ?ExpressionLanguage $interpreter = null
    ) {
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
            $loaderFactory = new Factory\Loader($this->interpreter);

            return $loaderFactory->compile($config);
        }

        throw new \RuntimeException('No suitable build with the provided configuration.');
    }
}
