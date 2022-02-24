<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter;

use Kiboko\Contract\Configurator\AdapterConfigurationInterface;

interface CloudFactoryInterface
{
    public function configuration(): AdapterConfigurationInterface;

    public function __invoke(array $configuration): \Psr\Http\Message\ResponseInterface;
}
