<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\State\Builder;

use PhpParser\Builder;
use PhpParser\Node;
use PhpParser\Node\Identifier;

final class RabbitMQBuilder implements Builder
{
    private ?Node\Expr $exchange = null;
    private ?Node\Expr $lineThreshold = null;

    public function __construct(
        private readonly Node\Expr $stepCode,
        private readonly Node\Expr $stepLabel,
        private readonly Node\Expr $topic,
    ) {}

    public function withExchange(
        Node\Expr $exchange,
    ): self {
        $this->exchange = $exchange;

        return $this;
    }

    public function withThreshold(
        Node\Expr $lineThreshold,
    ): self {
        $this->lineThreshold = $lineThreshold;

        return $this;
    }

    public function getNode(): Node\Expr
    {
        $args = [
           new Node\Arg(
               new Node\Expr\StaticCall(
                   class: new Node\Name\FullyQualified('Kiboko\\Component\\Flow\\RabbitMQ\\StateManager'),
                   name: 'withAuthentication',
                   args: [new Node\Arg(
                       value: new Node\Expr\New_(
                           class: new Node\Name\FullyQualified(
                               'Bunny\\Client',
                           ),
                       ),
                       name: new Node\Identifier('connection')
                    ), new Node\Arg(
                       value: $this->topic,
                       name: new Node\Identifier('topic')
                    ), $this->lineThreshold != null ? new Node\Arg(
                       value: $this->lineThreshold,
                       name: new Node\Identifier('lineThreshold')
                   ) : null, $this->exchange != null ? new Node\Arg(
                       value:  $this->exchange,
                       name: new Node\Identifier('exchange')
                    ) : null],
               ),
               name: new Node\Identifier('manager'),
           ),
            new Node\Arg($this->stepCode, name: new Node\Identifier('stepCode')),
            new Node\Arg($this->stepLabel, name: new Node\Identifier('stepLabel')),
        ];

        return new Node\Expr\New_(
            class: new Node\Name\FullyQualified('Kiboko\\Component\\Flow\\RabbitMQ\\State'),
            args: $args,
        );
    }
}
