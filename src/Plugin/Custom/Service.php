<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Custom;

use Kiboko\Component\Satellite\Plugin\Custom;
use Kiboko\Contract\Configurator;
use Symfony\Component\Config\Definition\Exception as Symfony;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

#[Configurator\Pipeline(
    name: "custom",
    steps: [
        new Configurator\Pipeline\StepExtractor(),
        new Configurator\Pipeline\StepTransformer(),
        new Configurator\Pipeline\StepLoader(),
    ],
)]
final class Service implements Configurator\PipelinePluginInterface
{
    private Processor $processor;
    private Configurator\PluginConfigurationInterface $configuration;
    private ExpressionLanguage $interpreter;

    public function __construct(
        ?ExpressionLanguage $interpreter = null
    ) {
        $this->processor = new Processor();
        $this->configuration = new Configuration();
        $this->interpreter = $interpreter ?? new ExpressionLanguage();
    }

    public function interpreter(): ExpressionLanguage
    {
        return $this->interpreter;
    }

    public function configuration(): Configurator\PluginConfigurationInterface
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
        } catch (Symfony\InvalidTypeException|Symfony\InvalidConfigurationException $exception) {
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

        if (array_key_exists('extractor', $config)) {
            $extractorFactory = new Custom\Factory\Extractor($this->interpreter);
            return $extractorFactory->compile($config['extractor']);
        } elseif (array_key_exists('transformer', $config)) {
            $transformerFactory = new Custom\Factory\Transformer($this->interpreter);
            return $transformerFactory->compile($config['transformer']);
        } elseif (array_key_exists('loader', $config)) {
            $loaderFactory = new Custom\Factory\Loader($this->interpreter);
            return $loaderFactory->compile($config['loader']);
        }

        throw new \RuntimeException('No possible pipeline step, expecting "extractor", "transformer" or "loader".');
    }
}
