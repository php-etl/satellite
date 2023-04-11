<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Diff;

use Kiboko\Component\Satellite\Cloud\Command;
use Kiboko\Component\Satellite\Cloud\DTO;

final readonly class ProbeListDiff
{
    private ProbeDiff $probeDiff;

    public function __construct(
        private DTO\PipelineId $pipelineId,
        private DTO\StepCode $stepCode,
    ) {
        $this->probeDiff = new ProbeDiff();
    }

    public function diff(DTO\ProbeList $left, DTO\ProbeList $right): DTO\CommandBatch
    {
        $commands = new DTO\CommandBatch();

        $leftIterator = $left->getIterator();
        $rightIterator = $right->getIterator();

        $iterator = new \MultipleIterator(\MultipleIterator::MIT_NEED_ANY);
        $iterator->attachIterator($leftIterator);
        $iterator->attachIterator($rightIterator);

        foreach ($iterator as [$currentLeft, $currentRight]) {
            try {
                $this->probeDiff->diff($currentLeft, $currentRight);
            } catch (CodeDoesNotMatchException|LabelDoesNotMatchException|OrderDoesNotMatchException) {
                $commands->push(
                    new Command\Pipeline\RemovePipelineStepProbeCommand($this->pipelineId, $this->stepCode, $currentLeft),
                    new Command\Pipeline\AddPipelineStepProbeCommand($this->pipelineId, $this->stepCode, $currentRight),
                );
            }
        }

        return $commands;
    }
}
