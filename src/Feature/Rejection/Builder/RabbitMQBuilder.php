<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\Rejection\Builder;

use PhpParser\Builder;
use PhpParser\Node;
use PhpParser\Node\Identifier;

final class RabbitMQBuilder implements Builder
{
    public ?Node\Expr $user = null;
    public ?Node\Expr $password = null;
    public ?Node\Expr $exchange = null;

    public function __construct(
        public Node\Expr $host,
        public Node\Expr $port,
        public Node\Expr $vhost,
        public Node\Expr $topic,
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

    public function getNode(): \PhpParser\Node\Expr
    {
        $args = [
            new Node\Arg($this->host, name: new Node\Identifier('host')),
            new Node\Arg($this->vhost, name: new Node\Identifier('vhost')),
            new Node\Arg($this->topic, name: new Node\Identifier('topic')),
            new Node\Arg(new Node\Expr\ConstFetch(new Node\Name('null')), name: new Node\Identifier('stepUuid')),
        ];

        if ($this->exchange !== null) {
            $args[] = new Node\Arg($this->exchange, name: new Node\Identifier('exchange'));
        }

        if ($this->port !== null) {
            $args[] = new Node\Arg($this->port, name: new Node\Identifier('port'));
        }

        if ($this->user !== null) {
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
        } else {
            return new Node\Expr\StaticCall(
                class: new Node\Name\FullyQualified('Kiboko\\Component\\Flow\\RabbitMQ\\Rejection'),
                name: new Identifier('withoutAuthentication'),
                args: $args,
            );
        }
    }
}
