<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Diff;

use Kiboko\Component\Satellite\Cloud\DTO\CommandBatch;
use Kiboko\Component\Satellite\Cloud\DTO\Probe;

final class ProbeDiff
{
    public function diff(Probe $left, Probe $right): CommandBatch
    {
        $commands = [];

        if ($left->code !== $right->code) {
            throw new CodeDoesNotMatchException('Code does not match between actual and desired probe definition.');
        }

        if ($left->label !== $right->label) {
            throw new LabelDoesNotMatchException('Label does not match between actual and desired probe definition.');
        }

        if ($left->order !== $right->order) {
            throw new OrderDoesNotMatchException('Order does not match between actual and desired probe definition.');
        }

        return new CommandBatch(...$commands);
    }
}
