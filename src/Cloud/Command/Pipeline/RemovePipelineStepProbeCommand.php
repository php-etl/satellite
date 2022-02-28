<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Command\Pipeline;

use Gyroscops\Api\Model\ProbeJsonld;
use Kiboko\Component\Satellite\Cloud\Command\Command;

final class RemovePipelineStepProbeCommand implements Command
{
    public function __construct(public string $pipeline, public string $stepCode, public ProbeJsonld $probe)
    {}
}
