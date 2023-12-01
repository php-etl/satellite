<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\State;

use Kiboko\Component\Satellite\ExpressionLanguage as Satellite;
use Kiboko\Contract\Configurator;
use Kiboko\Contract\Configurator\Feature;
use Symfony\Component\Config\Definition\Exception as Symfony;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

#[Feature(name: 'state')]
final readonly class Service implements Configurator\PipelineFeatureInterface
{
    private Processor $processor;
    private Configurator\FeatureConfigurationInterface $configuration;

    public function __construct(
        private ExpressionLanguage $interpreter = new Satellite\ExpressionLanguage(),
    ) {
        $this->processor = new Processor();
        $this->configuration = new Configuration();
    }

    public function interpreter(): ExpressionLanguage
    {
        return $this->interpreter;
    }

    public function configuration(): Configurator\FeatureConfigurationInterface
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
            if ($this->processor->processConfiguration($this->configuration, $config)) {
                return true;
            }
        } catch (\Exception) {
        }

        return false;
    }

    public function compile(array $config): Repository
    {
        $builder = new Builder\State();

        return new Repository($builder);
    }
}
