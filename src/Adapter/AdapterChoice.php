<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter;

use Kiboko\Component\Satellite;

final class AdapterChoice
{
    public function __construct(
        private array $adapters,
    ) {}

    public function __invoke(array $configuration): Satellite\SatelliteBuilderInterface
    {
        $factory = null;
        foreach ($this->adapters as $alias => $adapter) {
            if (array_key_exists($alias, $configuration)) {
                $factory = $adapter;
                break;
            }
        }

        assert($factory instanceof \Kiboko\Contract\Configurator\Adapter\FactoryInterface, new AdapterNotFoundException('No compatible adapter was found for your satellite configuration.'));

        return $factory($configuration);
    }
}
