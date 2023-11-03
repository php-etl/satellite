<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\State\Builder;

use PhpParser\Builder;
use PhpParser\Node;
use PhpParser\Node\Identifier;

final class RabbitMQBuilder implements Builder
{
    private ?Node\Expr $user = null;
    private ?Node\Expr $password = null;
    private ?Node\Expr $exchange = null;
    private ?Node\Expr $lineThreshold = null;

    public function __construct(
        private readonly Node\Expr $stepCode,
        private readonly Node\Expr $host,
        private readonly Node\Expr $port,
        private readonly Node\Expr $vhost,
        private readonly Node\Expr $topic,
    ) {}

    public function withAuthentication(
        Node\Expr $user,
        Node\Expr $password,
    ): self {
        $this->user = $user;
        $this->password = $password;

        return $this;
    }

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
                   args: array_filter([
                       new Node\Arg(
                           value: $this->host,
                           name: new Node\Identifier('host')
                        ),
                       new Node\Arg(
                           value: $this->vhost,
                           name: new Node\Identifier('vhost')
                        ),
                       new Node\Arg(
                           value: $this->topic,
                           name: new Node\Identifier('topic')
                        ),
                       new Node\Arg(
                           value: $this->user,
                           name: new Node\Identifier('user')
                       ),
                       new Node\Arg(
                           value: $this->password,
                           name: new Node\Identifier('password')
                        ),
                       new Node\Arg(
                           value: $this->port,
                           name: new Node\Identifier('port')
                       ),
                       $this->lineThreshold != null ? new Node\Arg(
                           value: $this->lineThreshold,
                           name: new Node\Identifier('lineThreshold')
                       ) : null,
                       $this->exchange != null ? new Node\Arg(
                           value:  $this->exchange,
                           name: new Node\Identifier('exchange')
                        ) : null,
                   ]),
               ),
               name: new Node\Identifier('manager'),
           ),
            new Node\Arg($this->stepCode, name: new Node\Identifier('stepCode')),
        ];

        return new Node\Expr\New_(
            class: new Node\Name\FullyQualified('Kiboko\\Component\\Flow\\RabbitMQ\\State'),
            args: $args,
        );
    }
}
