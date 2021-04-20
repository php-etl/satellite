<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\Logger\Factory\Repository;

use Kiboko\Contract\Configurator;
use Kiboko\Component\Satellite\Feature\Logger;

final class ElasticSearchRepository implements Configurator\RepositoryInterface
{
    use Logger\RepositoryTrait;

    public function __construct(private Logger\Builder\Monolog\ElasticSearchBuilder $builder)
    {
        $this->files = [];
        $this->packages = [];
    }

    public function getBuilder(): Logger\Builder\Monolog\ElasticSearchBuilder
    {
        return $this->builder;
    }
}
