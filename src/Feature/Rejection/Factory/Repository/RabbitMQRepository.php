<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\Rejection\Factory\Repository;

use Kiboko\Contract\Configurator;
use Kiboko\Component\Satellite\Feature\Rejection;

final class RabbitMQRepository implements Configurator\RepositoryInterface
{
    use Rejection\RepositoryTrait;

    public function __construct(private Rejection\Builder\RabbitMQBuilder $builder)
    {
        $this->files = [];
        $this->packages = [];
    }

    public function getBuilder(): Rejection\Builder\RabbitMQBuilder
    {
        return $this->builder;
    }
}
