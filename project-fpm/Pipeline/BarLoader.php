<?php declare(strict_types=1);

namespace Pipeline;

use Kiboko\Component\ETL\Contracts\LoaderInterface;

final class BarLoader implements LoaderInterface
{
    public function load(): \Generator
    {
        while ($line = yield) {
            file_put_contents('php://stdout', json_encode($line, JSON_OBJECT_AS_ARRAY) . PHP_EOL);
            yield $line;
        }
    }
}
