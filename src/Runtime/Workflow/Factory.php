<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Runtime\Workflow;

use Kiboko\Component\Satellite;
use Kiboko\Contract\Configurator;

#[Configurator\Runtime(name: 'workflow')] final readonly class Factory implements Satellite\Runtime\FactoryInterface
{
    private Configuration $configuration;

    public function __construct()
    {
        $this->configuration = new Configuration();
    }

    public function addFeature(string $name, Configurator\FactoryInterface $feature): self
    {
        $configuration = $feature->configuration();
        \assert($configuration instanceof Configurator\FeatureConfigurationInterface);

        $this->configuration->addFeature($name, $configuration);

        return $this;
    }

    public function addPlugin(string $name, Configurator\FactoryInterface $plugin): self
    {
        $configuration = $plugin->configuration();
        \assert($configuration instanceof Configurator\PluginConfigurationInterface);

        $this->configuration->addPlugin($name, $configuration);

        return $this;
    }

    public function configuration(): Configurator\RuntimeConfigurationInterface
    {
        return $this->configuration;
    }

    public function __invoke(array $configuration): Satellite\Runtime\RuntimeInterface
    {
        return new Runtime($configuration);
    }
}
