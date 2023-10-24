<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\DTO;

interface WorkflowInterface
{
    public function code(): string;

    public function label(): string;

    public function jobs(): JobList;

    public function autoload(): Autoload;

    public function packages(): PackageList;

    public function repositories(): RepositoryList;

    public function auths(): AuthList;
}
