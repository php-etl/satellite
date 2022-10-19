<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Transformer;

interface ConfigTransformerInterface
{
    public function transform(mixed $config): mixed;
}
