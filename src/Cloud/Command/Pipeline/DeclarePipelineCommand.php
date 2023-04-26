<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\Command\Pipeline;

use Kiboko\Component\Satellite\Cloud\Command\Command;
use Kiboko\Component\Satellite\Cloud\DTO;

final class DeclarePipelineCommand implements Command
{
    public function __construct(
        public string $code,
        public string $label,
        public DTO\StepList $steps,
        public DTO\Autoload $autoload,
        public DTO\PackageList $packages,
        public DTO\RepositoryList $repositories,
        public DTO\AuthList $auths,
        public DTO\OrganizationId $organizationId,
        public DTO\WorkspaceId $project,
    ) {
    }
}
