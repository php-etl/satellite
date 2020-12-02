<?php declare(strict_types=1);

namespace Pipeline;

use Kiboko\Component\ETL\Bucket\AcceptanceResultBucket;
use Kiboko\Component\ETL\Config\ArrayBuilder;
use Kiboko\Component\ETL\Contracts\TransformerInterface;
use Kiboko\Component\ETL\FastMap\Contracts\CompiledMapperInterface;

final class FastMapTransformer implements TransformerInterface
{
    private CompiledMapperInterface $mapper;

    public function __construct(CompiledMapperInterface $mapper)
    {
        $this->mapper = $mapper;
    }

    public function transform(): \Generator
    {
        $mapper = $this->mapper;

        while ($line = yield) {
            yield new AcceptanceResultBucket($mapper($line));
        }
    }
}
