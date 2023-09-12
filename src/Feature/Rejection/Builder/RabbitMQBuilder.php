<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\Rejection\Builder;

use PhpParser\Builder;
use PhpParser\Node;
use PhpParser\Node\Identifier;

final class RabbitMQBuilder implements Builder
{
    private ?Node\Expr $user = null;
    private ?Node\Expr $password = null;
    private ?Node\Expr $exchange = null;

    public function __construct(
        private readonly Node\Expr $stepUuid,
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

    public function getNode(): Node\Expr
    {
        $args = [
            new Node\Arg($this->host, name: new Node\Identifier('host')),
            new Node\Arg($this->vhost, name: new Node\Identifier('vhost')),
            new Node\Arg($this->topic, name: new Node\Identifier('topic')),
            new Node\Arg($this->stepUuid, name: new Node\Identifier('stepUuid')),
        ];

        if (null !== $this->exchange) {
            $args[] = new Node\Arg($this->exchange, name: new Node\Identifier('exchange'));
        }

        if (null !== $this->port) {
            $args[] = new Node\Arg($this->port, name: new Node\Identifier('port'));
        }

        if (null !== $this->user) {
            array_push(
                $args,
                new Node\Arg($this->user, name: new Node\Identifier('user')),
                new Node\Arg($this->password, name: new Node\Identifier('password')),
            );

            return new Node\Expr\StaticCall(
                class: new Node\Name\FullyQualified('Kiboko\\Component\\Flow\\RabbitMQ\\Rejection'),
                name: new Identifier('withAuthentication'),
                args: $args,
            );
        }

        return new Node\Expr\StaticCall(
            class: new Node\Name\FullyQualified('Kiboko\\Component\\Flow\\RabbitMQ\\Rejection'),
            name: new Identifier('withoutAuthentication'),
            args: $args,
        );
    }
}
