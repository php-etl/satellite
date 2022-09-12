<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cache;

use Kiboko\Component\Bucket\ComplexResultBucket;
use Kiboko\Contract\Pipeline\TransformerInterface;
use Psr\SimpleCache\CacheInterface;

final class Lookup implements \Kiboko\Contract\Pipeline\TransformerInterface
{
    public function __construct(
        private TransformerInterface $decorated,
        private string $mappingField,
        private CacheInterface $cache,
        private string $cacheKey
    ) {
    }

    public function transform(): \Generator
    {
        $line = yield;

        do {
            $bucket = new ComplexResultBucket();
            $output = $line;

            $result = $this->cache->get($this->cacheKey);

            if ($result !== null) {
                $output[$this->mappingField] = $result;
            } else {
                $output = yield $this->decorated->transform();

                $this->cache->set($this->cacheKey, $output[$this->mappingField]);
            }

            $bucket->accept($output);
        } while ($line = (yield $bucket));
    }
}
