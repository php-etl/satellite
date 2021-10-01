<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Adapter\Serverless;

interface ServerlessResourceInterface
{
    public function asArray(): array;
}
