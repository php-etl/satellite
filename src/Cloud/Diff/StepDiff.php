<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Diff;

use Kiboko\Component\Satellite\Cloud\DTO;

final class StepDiff
{
    public function __construct(
        private DTO\PipelineId $pipelineId,
    ) {
    }

    public function diff(DTO\Step $left, DTO\Step $right): DTO\CommandBatch
    {
        $commands = new DTO\CommandBatch();

        if ($left->code->asString() !== $right->code->asString()) {
            throw new CodeDoesNotMatchException('Code does not match between actual and desired step definition.');
        }

        if ($left->label !== $right->label) {
            throw new LabelDoesNotMatchException('Label does not match between actual and desired step definition.');
        }

        if ($left->order !== $right->order) {
            throw new OrderDoesNotMatchException('Order does not match between actual and desired step definition.');
        }

        $probeListDiff = new ProbeListDiff(
            $this->pipelineId,
            $right->code,
        );

        $commands->push(...$probeListDiff->diff($left->probes, $right->probes));

        return $commands;
    }
}
