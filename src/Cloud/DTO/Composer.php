<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Cloud\DTO;

final readonly class Composer
{
    public function __construct(
        private Autoload $autoload,
        private PackageList $packages,
        private RepositoryList $repositories,
        private AuthList $auths,
    ) {
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
