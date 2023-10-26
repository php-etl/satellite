<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Command\Workflow;

use Kiboko\Component\Satellite\Cloud\Command\Command;
use Kiboko\Component\Satellite\Cloud\DTO;

final class DeclareWorkflowCommand implements Command
{
    public function __construct(
        public string $code,
        public string $label,
        public DTO\JobList $jobs,
        public DTO\Composer $composer,
        public DTO\OrganizationId $organizationId,
        public DTO\WorkspaceId $project,
    ) {}
}
