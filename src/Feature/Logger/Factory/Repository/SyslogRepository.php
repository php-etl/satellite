<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\Logger\Factory\Repository;

use Kiboko\Component\Satellite\Feature\Logger;
use Kiboko\Contract\Configurator;

final class SyslogRepository implements Configurator\RepositoryInterface
{
    use Logger\RepositoryTrait;

    public function __construct(private readonly Logger\Builder\Monolog\SyslogBuilder $builder)
    {
        $this->files = [];
        $this->packages = [];
    }

    public function getBuilder(): Logger\Builder\Monolog\SyslogBuilder
    {
        return $this->builder;
    }
}
