<?php

namespace Kiboko\Component\Satellite\Feature\State\Builder;

use PhpParser\Builder;
use PhpParser\Node;

class RabbitMQManagerBuilder implements Builder
{
    public function __construct(
        private Node\Expr $connection,
        private Node\Expr $pipelineId,
        private Node\Expr $topic,
        private Node\Expr $lineThreshold,
    ) {
    }

    public function getNode(): Node\Expr
    {
        return new Node\Expr\Assign(
            var: new Node\Expr\Variable('stateManager'),
            expr: new Node\Expr\New_(
                class: new Node\Name\FullyQualified('Kiboko\Component\Flow\RabbitMQ\StateManager'),
                args: [
                    new Node\Arg(
                        value: $this->connection
                    ),
                    new Node\Arg(
                        value: $this->pipelineId
                    ),
                    new Node\Arg(
                        value: $this->topic
                    ),
                    new Node\Arg(
                        value: $this->lineThreshold
                    )
                ],
            ),
        );
    }
}
