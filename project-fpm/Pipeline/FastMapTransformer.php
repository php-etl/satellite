<?php declare(strict_types=1);

namespace Pipeline;

use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Component\FastMap\Contracts\CompiledMapperInterface;
use Kiboko\Contract\Pipeline\TransformerInterface;

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
