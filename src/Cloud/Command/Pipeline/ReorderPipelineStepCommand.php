<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Command\Pipeline;

use Kiboko\Component\Satellite\Cloud\Command\Command;
use Kiboko\Component\Satellite\Cloud\DTO\PipelineId;

final class ReorderPipelineStepCommand implements Command
{
    public function __construct(
        public PipelineId $pipeline,
        public array $codes,
    ) {}
}
