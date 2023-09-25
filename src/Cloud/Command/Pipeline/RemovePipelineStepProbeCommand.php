<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Command\Pipeline;

use Kiboko\Component\Satellite\Cloud\Command\Command;
use Kiboko\Component\Satellite\Cloud\DTO;

final class RemovePipelineStepProbeCommand implements Command
{
    public function __construct(
        public DTO\PipelineId $pipeline,
        public DTO\StepCode $stepCode,
        public DTO\Probe $probe,
    ) {}
}
