<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Command\Workflow;

use Kiboko\Component\Satellite\Cloud\Command\Command;
use Kiboko\Component\Satellite\Cloud\DTO\WorkflowId;

final class RemoveWorkflowCommand implements Command
{
    public function __construct(
        public WorkflowId $id,
    ) {
    }
}
