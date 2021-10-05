<?php

namespace Kiboko\Component\Satellite\Feature\State\Builder;

use PhpParser\Builder;
use PhpParser\Node;

class RabbitMQBuilder implements Builder
{
    private ?Node\Expr $stepCode = null;
    private ?Node\Expr $stepName = null;

    public function __construct(
    ) {
    }

    public function withStepInfo(Node\Expr $stepName, Node\Expr $stepCode): self
    {
        $this->stepName = $stepName;
        $this->stepCode = $stepCode;

        return $this;
    }

    public function getNode(): Node\Expr
    {
        return new Node\Expr\New_(
            class: new Node\Name\FullyQualified('Kiboko\Component\Flow\RabbitMQ\State'),
            args: [
                new Node\Arg(
                    value: new Node\Expr\New_(
                        class: new Node\Name\FullyQualified('\Kiboko\Component\Flow\RabbitMQ\StateManager'),
                        args: [
                            new Node\Arg()
                        ]
                    ),
                ),
                new Node\Arg(
                    value: $this->stepCode
                ),
                new Node\Arg(
                    value: $this->stepName
                ),
            ],
        );
    }
}
