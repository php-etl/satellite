<?php

declare(strict_types=1);

namespace unit\Kiboko\Component\Satellite\Cloud;

use Kiboko\Component\Satellite\Cloud\ContextInterface;
use Kiboko\Component\Satellite\Cloud\DTO\OrganizationId;
use Kiboko\Component\Satellite\Cloud\DTO\WorkspaceId;

final readonly class DummyContext implements ContextInterface
{
    public function changeOrganization(OrganizationId $organization): void
    {
        // Todo: write this method
    }

    public function organization(): OrganizationId
    {
        return new OrganizationId('93af28a3-c7f9-4392-a013-b4ece257ecf5');
    }

    public function changeWorkspace(WorkspaceId $workspace): void
    {
        // Todo: write this method
    }

    public function workspace(): WorkspaceId
    {
        return new WorkspaceId('eb6ed674-5198-4be2-a8b9-8ef5d5292be4');
    }
}
