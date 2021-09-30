<?php

namespace Kiboko\Component\Satellite\Feature\State\Builder;

use PhpParser\Builder;
use PhpParser\Node;

class RabbitMQBuilder implements Builder
{
    public function __construct(
        private Node\Expr $stepCode,
        private Node\Expr $stepLabel,
    ) {
    }

    public function getNode(): Node\Expr
    {
        return new Node\Expr\MethodCall(
            var: new Node\Expr\Variable('stateManager'),
            name: new Node\Name('stepState'),
            args: [
                new Node\Arg(
                    value: $this->stepCode
                ),
                new Node\Arg(
                    value: $this->stepLabel
                )
            ]
        );
    }
}
