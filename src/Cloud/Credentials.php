<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud;

use Kiboko\Component\Satellite\Cloud\DTO\OrganizationId;
use Kiboko\Component\Satellite\Cloud\DTO\WorkspaceId;

final class Credentials
{
    public function __construct(
        public string $username,
        public string $password,
        public ?OrganizationId $organization = null,
        public ?WorkspaceId $workspace = null,
    ) {
    }

    public function __debugInfo(): ?array
    {
        return [
            'login' => $this->username,
            'password' => '**SECRET**',
            'organization' => $this->organization,
            'workspace' => $this->workspace,
        ];
    }
}
