<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter;

use Kiboko\Contract\Configurator\AdapterConfigurationInterface;

interface CloudFactoryInterface
{
    public function configuration(): AdapterConfigurationInterface;

    public function create(array $configuration): void;

    public function update(array $configuration): void;

    public function remove(array $configuration): void;
}
