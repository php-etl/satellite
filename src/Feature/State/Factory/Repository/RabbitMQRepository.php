<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\State\Factory\Repository;

use Kiboko\Component\Satellite\Feature\State;
use Kiboko\Contract\Configurator;

final class RabbitMQRepository implements Configurator\RepositoryInterface
{
    use State\RepositoryTrait;

    public function __construct(private readonly State\Builder\RabbitMQBuilder $builder)
    {
        $this->files = [];
        $this->packages = [
            'php-etl/rabbitmq-flow',
        ];
    }

    public function getBuilder(): State\Builder\RabbitMQBuilder
    {
        return $this->builder;
    }
}
