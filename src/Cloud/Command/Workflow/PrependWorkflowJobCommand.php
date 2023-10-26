<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Command\Workflow;

use Kiboko\Component\Satellite\Cloud\Command\Command;
use Kiboko\Component\Satellite\Cloud\DTO;

final class PrependWorkflowJobCommand implements Command
{
    public function __construct(
        public DTO\WorkflowId $workflowId,
        public DTO\Workflow\JobInterface $job,
    ) {}
}
