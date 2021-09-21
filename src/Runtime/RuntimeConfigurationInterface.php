<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Runtime;

use Kiboko\Component\Satellite;
use Symfony\Component\Config\Definition\ConfigurationInterface;

interface RuntimeConfigurationInterface extends ConfigurationInterface
{
    public function addPlugin(string $name, Satellite\Plugin\PluginConfigurationInterface $plugin): self;
    public function addFeature(string $name, Satellite\Feature\FeatureConfigurationInterface $feature): self;
}
