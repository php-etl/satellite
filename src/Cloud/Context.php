<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud;

use Gyroscops\Api;
use Kiboko\Component\Satellite\Cloud\DTO\OrganizationId;
use Kiboko\Component\Satellite\Cloud\DTO\WorkspaceId;

final class Context
{
    public function __construct(
        private readonly Api\Client $client,
        private readonly Auth $auth,
        private readonly string $url,
    ) {}

    public function changeOrganization(OrganizationId $organization): void
    {
        $token = $this->auth->changeOrganization($this->client, $organization);
        $this->auth->persistOrganization($this->url, $organization);
        $this->auth->persistToken($this->url, $token);
        $this->auth->flush();
    }

    public function organization(): OrganizationId
    {
        $organization = $this->auth->credentials($this->url)->organization;
        if ($organization === null) {
            throw new NoOrganizationSelectedException('Could not determine the current organization.');
        }

        return $organization;
    }

    public function changeWorkspace(WorkspaceId $workspace): void
    {
        $token = $this->auth->changeWorkspace($this->client, $workspace);
        $this->auth->persistWorkspace($this->url, $workspace);
        $this->auth->persistToken($this->url, $token);
        $this->auth->flush();
    }

    public function workspace(): WorkspaceId
    {
        $workspace = $this->auth->credentials($this->url)->workspace;
        if ($workspace === null) {
            throw new NoWorkspaceSelectedException('Could not determine the current workspace.');
        }

        return $workspace;
    }
}
