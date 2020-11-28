<?php declare(strict_types=1);

namespace Kiboko\Component\ETL\Satellite\Runtime;

interface RuntimeInterface
{
    public function build(): array;
}
