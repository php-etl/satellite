<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\Rejection\Factory\Repository;

use Kiboko\Component\Satellite\Feature\Rejection;
use Kiboko\Contract\Configurator;

final class RabbitMQRepository implements Configurator\RepositoryInterface
{
    use Rejection\RepositoryTrait;

    public function __construct(private readonly Rejection\Builder\RabbitMQBuilder $builder)
    {
        $this->files = [];
        $this->packages = [
            'php-etl/rabbitmq-flow:^0.2',
        ];
    }

    public function getBuilder(): Rejection\Builder\RabbitMQBuilder
    {
        return $this->builder;
    }
}
