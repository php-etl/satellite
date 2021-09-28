<?php

namespace Kiboko\Component\Satellite\Feature\State\Builder;

use PhpParser\Builder;
use PhpParser\Node;

class RabbitMQBuilder implements Builder
{
    private ?Node\Expr $user = null;
    private ?Node\Expr $password = null;
    private ?Node\Expr $exchange = null;
    public ?Node\Expr $port = null;

    public function __construct(
        public Node\Expr $host,
        public Node\Expr $vhost,
        private Node\Expr $pipelineId,
        private Node\Expr $stepCode,
        private Node\Expr $stepLabel,
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

    public function withAuthentication(Node\Expr $user, Node\Expr $password)
    {
        $this->user = $user;
        $this->password = $password;
    }

    public function getNode(): Node\Expr
    {
        return new Node\Expr\StaticCall(
            class: new Node\Name\FullyQualified('Kiboko\\Component\\Flow\\RabbitMQ\\State'),
            name: ($this->user && $this->password) ? 'withAuthentication' : 'withoutAuthentication',
            args: array_filter([
                new Node\Arg(
                    value: $this->pipelineId,
                ),
                new Node\Arg(
                    value: $this->stepCode,
                ),
                new Node\Arg(
                    value: $this->stepLabel,
                ),
                new Node\Arg(
                    value: $this->host,
                ),
                new Node\Arg(
                    value: $this->vhost,
                ),
                new Node\Arg(
                    value: $this->topic,
                ),
                $this->user ? new Node\Arg(
                    value: $this->user,
                ) : null,
                $this->password ? new Node\Arg(
                    value: $this->password,
                ) : null,
                $this->exchange ? new Node\Arg(
                    value: $this->exchange,
                ) : null,
                $this->port ? new Node\Arg(
                    value: $this->port,
                ) : null
            ])
        );
    }
}
