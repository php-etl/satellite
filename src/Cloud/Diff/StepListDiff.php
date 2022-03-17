<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Diff;

use Kiboko\Component\Satellite\Cloud\Command\Pipeline\AddAfterPipelineStepCommand;
use Kiboko\Component\Satellite\Cloud\Command\Pipeline\AppendPipelineStepCommand;
use Kiboko\Component\Satellite\Cloud\Command\Pipeline\PrependPipelineStepCommand;
use Kiboko\Component\Satellite\Cloud\Command\Pipeline\RemovePipelineStepCommand;
use Kiboko\Component\Satellite\Cloud\Command\Pipeline\ReorderPipelineStepCommand;
use Kiboko\Component\Satellite\Cloud\Command\Pipeline\ReplacePipelineStepCommand;
use Kiboko\Component\Satellite\Cloud\DTO;

final class StepListDiff
{
    public function __construct(
        private DTO\PipelineId $pipelineId,
    ) {}

    public function diff(DTO\StepList $left, DTO\StepList $right): DTO\CommandBatch
    {
        $commands = new DTO\CommandBatch();

        $leftPositions = $left->codes();
        $rightPositions = $right->codes();

        if (count($leftPositions) > count($rightPositions)) {
            $movingSteps = [];

            $index = 0;
            foreach ($leftPositions as $oldPosition => $code) {
                $desiredPosition = array_search($code, $rightPositions, true);

                // If the $left code does not exist in the $right list, then the step must be removed or replaced
                if ($desiredPosition === false) {
                    $index++;
                    // If the $left code position is equal to the $index, the step must be removed,
                    // otherwise the step is replaced by another one
                    if ($oldPosition >= $index) {
                        $commands->push(new RemovePipelineStepCommand($this->pipelineId, new DTO\StepCode($code)));
                    } else {
                        $commands->push(
                            new ReplacePipelineStepCommand(
                                $this->pipelineId,
                                new DTO\StepCode($code),
                                $right->get($rightPositions[$oldPosition])
                            )
                        );
                    }
                }

                // If the $left code exist in the $right list and that the older position is different from the desired one
                if (($desiredPosition !== false) && $oldPosition !== $desiredPosition) {
                    $movingSteps[$desiredPosition] = $code;
                }
            }

            sort($movingSteps);
            $commands->push(
                new ReorderPipelineStepCommand(
                    $this->pipelineId,
                    $movingSteps
                )
            );
        } else {
            $index = 0;
            foreach ($rightPositions as $desiredPosition => $code) {
                $currentPosition = array_search($code, $leftPositions, true);

                // If the $right code does not exist in the $left list, then the step must be added
                if ($currentPosition === false) {
                    $index++;
                    if ($desiredPosition === 0) {
                        $commands->push(
                            new PrependPipelineStepCommand(
                                $this->pipelineId,
                                $right->get($code)
                            )
                        );
                    }

                    if ($currentPosition > count($leftPositions)) {
                        $commands->push(new AppendPipelineStepCommand($this->pipelineId, $right->get($code)));
                    }
                }
            }

            $commands->push(new ReorderPipelineStepCommand($this->pipelineId, $rightPositions));
        }

        return $commands;
    }
}
