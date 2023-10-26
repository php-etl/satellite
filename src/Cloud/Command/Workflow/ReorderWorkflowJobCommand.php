<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Command\Workflow;

use Kiboko\Component\Satellite\Cloud\Command\Command;
use Kiboko\Component\Satellite\Cloud\DTO;

class ReorderWorkflowJobCommand implements Command
{
    /** @var list<DTO\JobCode> */
    public array $codes;

    public function __construct(
        public DTO\WorkflowId $workflowId,
        DTO\JobCode ...$codes,
    ) {
        $this->codes = $codes;
    }
}
