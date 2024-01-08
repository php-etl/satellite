<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Diff;

use Kiboko\Component\Satellite\Cloud\DTO;

final readonly class JobListDiff
{
    public function __construct(
        private DTO\WorkflowId $workflowId,
    ) {
    }

    public function diff(DTO\JobList $left, DTO\JobList $right): DTO\CommandBatch
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
                $commands->push(new \Kiboko\Component\Satellite\Cloud\Command\Workflow\PrependWorkflowJobCommand($this->workflowId, $right->get($code)));
            } else {
                $commands->push(new \Kiboko\Component\Satellite\Cloud\Command\Workflow\AddAfterWorkflowJobCommand($this->workflowId, new DTO\JobCode($rightPositions[$desiredPosition - 1]), $right->get($code)));
            }
        }

        $offset = 0;
        $needsReorder = false;
        foreach ($leftPositions as $currentPosition => $code) {
            // If the $left code does not exist in the $right list, then the step must be removed
            if (($desiredPosition = array_search($code, $rightPositions, true)) === false) {
                ++$offset;
                $commands->push(new \Kiboko\Component\Satellite\Cloud\Command\Workflow\RemoveWorkflowJobCommand($this->workflowId, new DTO\StepCode($code)));
                continue;
            }

            if (($desiredPosition + $offset) > $currentPosition) {
                $needsReorder = true;
            }
        }

        if (true === $needsReorder) {
            $commands->push(new \Kiboko\Component\Satellite\Cloud\Command\Workflow\ReorderWorkflowJobCommand(
                $this->workflowId,
                ...array_map(fn (string $code) => new DTO\JobCode($code), $rightPositions)
            ));
        }

        return $commands;
    }
}
