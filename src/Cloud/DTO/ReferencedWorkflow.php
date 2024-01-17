<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\DTO;

final readonly class ReferencedWorkflow implements WorkflowInterface
{
    public function __construct(
        private WorkflowId $id,
        private Workflow $decorated,
    ) {
    }

    public function id(): WorkflowId
    {
        return $this->id;
    }

    public function code(): string
    {
        return $this->decorated->code();
    }

    public function label(): string
    {
        return $this->decorated->label();
    }

    public function jobs(): JobList
    {
        return $this->decorated->jobs();
    }

    public function autoload(): Autoload
    {
        return $this->decorated->composer()->autoload();
    }

    public function packages(): PackageList
    {
        return $this->decorated->composer()->packages();
    }

    public function repositories(): RepositoryList
    {
        return $this->decorated->composer()->repositories();
    }

    public function auths(): AuthList
    {
        return $this->decorated->composer()->auths();
    }
}
