<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Diff;

use Kiboko\Component\Satellite\Cloud\Command\Pipeline\AppendPipelineStepCommand;
use Kiboko\Component\Satellite\Cloud\Command\Pipeline\MoveAfterPipelineStepCommand;
use Kiboko\Component\Satellite\Cloud\Command\Pipeline\MoveBeforePipelineStepCommand;
use Kiboko\Component\Satellite\Cloud\Command\Pipeline\RemovePipelineStepCommand;
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

        $offset = 0;
        foreach ($leftPositions as $currentPosition => $code) {
            // If the $left code does not exist in the $right list, then the step must be removed
            if (($desiredPosition = array_search($code, $rightPositions, true)) === false) {
                $offset++;
                $commands->push(new RemovePipelineStepCommand($this->pipelineId, new DTO\StepCode($code)));
                continue;
            }

            if (($desiredPosition + $offset) <= $currentPosition) {
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

            if ($currentPosition <= ($desiredPosition + $offset)) {
                continue;
            }

            if ($desiredPosition === 0) {
                $commands->push(new MoveBeforePipelineStepCommand(
                    $this->pipelineId,
                    new DTO\StepCode($rightPositions[0]),
                    new DTO\StepCode($code)
                ));
                continue;
            }
            // If the $search is greater than $actual, then we should move the step at the beginning of the list
            $commands->push(new MoveBeforePipelineStepCommand(
                $this->pipelineId,
                new DTO\StepCode($rightPositions[$desiredPosition - 1]),
                new DTO\StepCode($code)
            ));
        }

        $test = array_diff($leftPositions, $rightPositions);
        $a = array_diff_assoc($leftPositions, $rightPositions);

        // On compare la longueur de nos 2 tableaux, s'ils ont la même longueur, alors on a pas de suppression ou d'ajout
        if ((count($leftPositions) === count($rightPositions))) {
            $index = 0;
            while ($index <= count($rightPositions)) {
                // On compare la valeur actuelle de nos 2 tableaux, si elles sont différentes et que le code du nouveau tableau
                // n'est pas dans le tableau initial alors on remplace
                if ($leftPositions[$index] !== $rightPositions[$index] && !array_key_exists($rightPositions[$index], $rightPositions)) {
                    $code = $rightPositions[$index];

                    $commands->push(new ReplacePipelineStepCommand(
                        $this->pipelineId,
                        new DTO\StepCode($leftPositions[$index]),
                        new DTO\Step(
                            $right->get($code)->label,
                            $right->get($code)->code,
                            $right->get($code)->config,
                            $right->get($code)->probes,
                            $right->get($code)->order,
                        )
                    ));
                }

                $index++;
            }
        }

        return $commands;
    }
}
