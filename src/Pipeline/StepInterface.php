<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Pipeline;

use Kiboko\Component\Satellite\Builder\Pipeline;
use Kiboko\Contract\Configurator\StepRepositoryInterface;

interface StepInterface
{
    public function __invoke(array $config, Pipeline $pipeline, StepRepositoryInterface $repository): void;
}
