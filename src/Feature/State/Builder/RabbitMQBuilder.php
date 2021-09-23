<?php

namespace Kiboko\Component\Satellite\Feature\State\Builder;

use PhpParser\Builder;
use PhpParser\Node;

class RabbitMQBuilder implements Builder
{
    private ?Node\Expr $exchange = null;
    public ?Node\Expr $port = null;
    private ?Node\Expr $vhost = null;

    public function __construct(
        public Node\Expr $host,
        public Node\Expr $user,
        public Node\Expr $password,
        public Node\Expr $topic,
    ) {
    }

    public function withExchange(Node\Expr $exchange): self
    {
        $this->exchange = $exchange;

        return $this;
    }

    public function withPort(Node\Expr $port): self
    {
        $this->port = $port;

        return $this;
    }

    public function withVhost(Node\Expr $vhost): self
    {
        $this->vhost = $vhost;

        return $this;
    }

    public function getNode(): Node\Expr
    {
        return new Node\Expr\New_(
            class: new Node\Name\FullyQualified('Kiboko\\Component\\Flow\\RabbitMQ\\RabbitState'),
            args: array_filter([
                new Node\Arg(
                    value: $this->host
                ),
                new Node\Arg(
                    value: $this->user
                ),
                new Node\Arg(
                    value: $this->password
                ),
                new Node\Arg(
                    value: $this->topic
                ),
                $this->port ?  new Node\Arg(
                    value: $this->port
                ) : null
            ])
        );
    }
}
