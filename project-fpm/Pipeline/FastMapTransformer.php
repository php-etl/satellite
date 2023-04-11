<?php declare(strict_types=1);

namespace Pipeline;

use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Contract\Mapping\CompiledMapperInterface;
use Kiboko\Contract\Pipeline\TransformerInterface;

final readonly class FastMapTransformer implements TransformerInterface
{
    public function __construct(private CompiledMapperInterface $mapper)
    {
    }

    public function transform(): \Generator
    {
        $mapper = $this->mapper;

        while ($line = yield) {
            yield new AcceptanceResultBucket($mapper($line));
        }
    }
}
