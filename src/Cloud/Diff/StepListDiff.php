<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Diff;

use Kiboko\Component\Satellite\Cloud\Command\Pipeline;
use Kiboko\Component\Satellite\Cloud\DTO;

final class StepListDiff
{
    public function __construct(
        private DTO\PipelineId $pipelineId,
    ) {
    }

    public function diff(DTO\StepList $left, DTO\StepList $right): DTO\CommandBatch
    {
        $commands = new DTO\CommandBatch();

        $leftPositions = $left->codes();
        $rightPositions = $right->codes();

        foreach ($rightPositions as $desiredPosition => $code) {
            // If the $right code does not exist in the $left list, then the step must be added
            if (false !== array_search($code, $leftPositions, true)) {
                continue;
            }

            if (0 === $desiredPosition) {
                $commands->push(new Pipeline\PrependPipelineStepCommand($this->pipelineId, $right->get($code)));
            } else {
                $commands->push(new Pipeline\AddAfterPipelineStepCommand($this->pipelineId, new DTO\StepCode($rightPositions[$desiredPosition - 1]), $right->get($code)));
            }
        }

        $offset = 0;
        $needsReorder = false;
        foreach ($leftPositions as $currentPosition => $code) {
            // If the $left code does not exist in the $right list, then the step must be removed
            if (($desiredPosition = array_search($code, $rightPositions, true)) === false) {
                ++$offset;
                $commands->push(new Pipeline\RemovePipelineStepCommand($this->pipelineId, new DTO\StepCode($code)));
                continue;
            }

            if (($desiredPosition + $offset) > $currentPosition) {
                $needsReorder = true;
            }
        }

        if (true === $needsReorder) {
            $commands->push(new Pipeline\ReorderPipelineStepCommand(
                $this->pipelineId,
                ...array_map(fn (string $code) => new DTO\StepCode($code), $rightPositions)
            ));
        }

        return $commands;
    }
}
