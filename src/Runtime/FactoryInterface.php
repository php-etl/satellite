<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Runtime;

interface FactoryInterface
{
    public function __invoke(array $configuration): RuntimeInterface;
}
