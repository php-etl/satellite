<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Runtime\HttpHook;

use Kiboko\Component\Satellite;

final class Factory implements Satellite\Runtime\FactoryInterface
{
    public function __invoke(array $configuration): Satellite\Runtime\RuntimeInterface
    {
        return new Runtime($configuration);
    }
}
