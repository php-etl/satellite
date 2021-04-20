<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Pipeline;

use Kiboko\Component\Satellite\Builder\Pipeline;
use Kiboko\Contract\Configurator\RepositoryInterface;

final class Extractor implements StepInterface
{
    public function __construct(private ?string $key)
    {}

    public function __invoke(array $config, Pipeline $pipeline, RepositoryInterface $repository): void
    {
        if ($this->key !== null && !array_key_exists($this->key, $config)) {
            return;
        }
        $pipeline->addExtractor($repository->getBuilder());
    }
}
