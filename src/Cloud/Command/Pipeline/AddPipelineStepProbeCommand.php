<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Command\Pipeline;

use Gyroscops\Api\Model\Probe;
use Kiboko\Component\Satellite\Cloud\Command\Command;

final class AddPipelineStepProbeCommand implements Command
{
    public function __construct(
        public string $pipeline,
        public string $stepCode,
        public Probe $probe
    )
    {}
}
