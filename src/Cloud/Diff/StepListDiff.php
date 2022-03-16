<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Diff;

use Kiboko\Component\Satellite\Cloud\Command\Pipeline\AddAfterPipelineStepCommand;
use Kiboko\Component\Satellite\Cloud\Command\Pipeline\AppendPipelineStepCommand;
use Kiboko\Component\Satellite\Cloud\Command\Pipeline\MoveAfterPipelineStepCommand;
use Kiboko\Component\Satellite\Cloud\Command\Pipeline\MoveBeforePipelineStepCommand;
use Kiboko\Component\Satellite\Cloud\Command\Pipeline\PrependPipelineStepCommand;
use Kiboko\Component\Satellite\Cloud\Command\Pipeline\RemovePipelineStepCommand;
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

        foreach ($leftPositions as $currentPosition => $code) {
            // If the $left code does not exist in the $right list, then the step must be removed
            if (($desiredPosition = array_search($code, $rightPositions, true)) === false) {
                $removed[] = $code;
                $commands->push(new RemovePipelineStepCommand($this->pipelineId, new DTO\StepCode($code)));
                continue;
            }

            if ($desiredPosition <= $currentPosition) {
                continue;
            }
            // If the $search (current) is greater than $actual (actual), then we should move the step at the end of the list
            $commands->push(new MoveAfterPipelineStepCommand($this->pipelineId, new DTO\StepCode($rightPositions[$desiredPosition - 1]), new DTO\StepCode($code)));
        }

        foreach ($rightPositions as $desiredPosition => $code) {
            // If the $right code does not exist in the $left list, then the step must be added
            if (($currentPosition = array_search($code, $leftPositions, true)) === false) {
                $commands->push(new AppendPipelineStepCommand($this->pipelineId, $right->get($code)));
                continue;
            }

            if ($currentPosition <= $desiredPosition) {
                continue;
            }

            // If the $search is greater than $actual, then we should move the step at the beginning of the list
            $commands->push(new MoveBeforePipelineStepCommand($this->pipelineId, new DTO\StepCode($rightPositions[$desiredPosition - 1]), new DTO\StepCode($code)));
        }

        return $commands;
    }
}
