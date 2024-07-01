<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud;

use Kiboko\Component\Satellite\Cloud\DTO\OrganizationId;
use Kiboko\Component\Satellite\Cloud\DTO\WorkspaceId;

interface ContextInterface
{
    public function changeOrganization(OrganizationId $organization): void;

    public function organization(): OrganizationId;

    public function changeWorkspace(WorkspaceId $workspace): void;

    public function workspace(): WorkspaceId;
}
