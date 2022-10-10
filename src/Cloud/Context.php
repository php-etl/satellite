<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud;

use Kiboko\Component\Satellite\Cloud\DTO\OrganizationId;
use Kiboko\Component\Satellite\Cloud\DTO\WorkspaceId;

final class Context
{
    private string $pathName;
    private array $configuration;

    public function __construct(?string $pathName = null)
    {
        if (null === $pathName) {
            $this->pathName = getcwd();
        } else {
            $this->pathName = $pathName;
        }

        if (!file_exists($this->pathName)) {
            throw new \RuntimeException(sprintf('Directory "%s" does not exist.', $this->pathName));
        }

        $content = false;
        if (file_exists($this->pathName.'/.gyroscops.json')) {
            $content = file_get_contents($this->pathName.'/.gyroscops.json');
        } else {
            touch($this->pathName.'/.gyroscops.json');
            chmod($this->pathName.'/.gyroscops.json', 0o655);
        }

        if (false === $content) {
            $this->configuration = [];

            return;
        }

        try {
            $this->configuration = json_decode($content, associative: true, flags: \JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            $this->configuration = [];
        }
    }

    public function dump(): void
    {
        $content = json_encode($this->configuration, flags: \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT);

        file_put_contents($this->pathName.'/.gyroscops.json', $content);
    }

    public function changeOrganization(OrganizationId $organization): void
    {
        $this->configuration['organization'] = $organization->asString();
        unset($this->configuration['project']);
    }

    public function organization(): OrganizationId
    {
        if (!\array_key_exists('organization', $this->configuration)) {
            throw new NoOrganizationSelectedException('Could not determine the current organization.');
        }

        return new OrganizationId($this->configuration['organization']);
    }

    public function changeWorkspace(WorkspaceId $project): void
    {
        $this->configuration['project'] = $project->asString();
    }

    public function workspace(): WorkspaceId
    {
        if (!\array_key_exists('project', $this->configuration)) {
            throw new NoWorkspaceSelectedException('Could not determine the current project.');
        }

        return new WorkspaceId($this->configuration['project']);
    }
}
