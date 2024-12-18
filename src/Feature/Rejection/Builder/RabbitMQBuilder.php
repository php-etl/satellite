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
    ) {
    }

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
            new Node\Arg($this->host, name: new Identifier('host')),
            new Node\Arg($this->vhost, name: new Identifier('vhost')),
        ];

        if (null !== $this->port) {
            $args[] = new Node\Arg($this->port, name: new Identifier('port'));
        }

        if (null !== $this->user) {
            array_push(
                $args,
                new Node\Arg($this->user, name: new Identifier('user')),
                new Node\Arg($this->password, name: new Identifier('password')),
            );
        }

        return new Node\Expr\New_(
            class: new Node\Name\FullyQualified('Kiboko\\Component\\Flow\\RabbitMQ\\Rejection'),
            args: [
                new Node\Arg(
                    new Node\Expr\StaticCall(
                        class: new Node\Name\FullyQualified('Kiboko\\Component\\Flow\\RabbitMQ\\ClientMiddleware'),
                        name: new Node\Name('getInstance'),
                        args: $args,
                    ),
                ),
                new Node\Arg($this->topic, name: new Identifier('topic')),
                new Node\Arg($this->stepUuid, name: new Identifier('stepUuid')),
                $this->exchange !== null ? new Node\Arg($this->exchange, name: new Identifier('exchange')) : new Node\Arg(new Node\Expr\ConstFetch(new Node\Name('null')), name: new Identifier('exchange')),
            ],
        );
    }
}
