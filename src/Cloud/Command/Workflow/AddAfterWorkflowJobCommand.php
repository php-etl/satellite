<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Command\Workflow;

use Kiboko\Component\Satellite\Cloud\Command\Command;
use Kiboko\Component\Satellite\Cloud\DTO;

final class AddAfterWorkflowJobCommand implements Command
{
    public function __construct(
        DTO\WorkflowId $workflowId,
        DTO\JobCode $code,
        DTO\Workflow\JobInterface $job,
    ) {}
}
