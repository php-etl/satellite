<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\DTO;

final readonly class Pipeline implements PipelineInterface
{
    public function __construct(
        private string $label,
        private string $code,
        private StepList $steps,
        private Autoload $autoload = new Autoload(),
        private PackageList $packages = new PackageList(),
        private RepositoryList $repositories = new RepositoryList(),
        private AuthList $auths = new AuthList(),
    ) {
    }

    public function code(): string
    {
        return $this->code;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function steps(): StepList
    {
        return $this->steps;
    }

    public function autoload(): Autoload
    {
        return $this->autoload;
    }

    public function packages(): PackageList
    {
        return $this->packages;
    }

    public function repositories(): RepositoryList
    {
        return $this->repositories;
    }

    public function auths(): AuthList
    {
        return $this->auths;
    }
}
