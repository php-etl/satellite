<?php declare(strict_types=1);

namespace Pipeline;

use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Component\Config\ArrayBuilder;
use Kiboko\Component\Contracts\TransformerInterface;
use Kiboko\Component\FastMap\Contracts\CompiledMapperInterface;

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
