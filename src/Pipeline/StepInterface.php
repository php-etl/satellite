<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Pipeline;

use Kiboko\Component\Satellite\Builder\Pipeline;
use Kiboko\Contract\Configurator\RepositoryInterface;

interface StepInterface
{
    public function __invoke(array $config, Pipeline $pipeline, RepositoryInterface $repository): void;
}
