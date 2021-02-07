<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite;

use Symfony\Component\Config\Definition\ConfigurationInterface;

interface NamedConfigurationInterface extends ConfigurationInterface
{
    public function getName(): string;
}
