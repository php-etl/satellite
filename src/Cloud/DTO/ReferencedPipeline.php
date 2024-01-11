<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\DTO;

final readonly class ReferencedPipeline implements PipelineInterface
{
    public function __construct(
        private PipelineId $id,
        private Pipeline $decorated,
    ) {
    }

    public function id(): PipelineId
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

    public function steps(): StepList
    {
        return $this->decorated->steps();
    }

    public function autoload(): Autoload
    {
        return $this->decorated->autoload();
    }

    public function packages(): PackageList
    {
        return $this->decorated->packages();
    }

    public function repositories(): RepositoryList
    {
        return $this->decorated->repositories();
    }

    public function auths(): AuthList
    {
        return $this->decorated->auths();
    }
}
