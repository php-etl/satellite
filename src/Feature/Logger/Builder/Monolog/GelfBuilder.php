<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\Logger\Builder\Monolog;

use PhpParser\Node;

final class GelfBuilder implements MonologBuilderInterface
{
    private string $transport = 'tcp';
    private ?string $level = null;
    private ?string $vhost = null;
    private ?string $host = null;
    private ?int $port = null;
    private ?float $timeout = null;
    private ?string $queue = null;
    private ?string $channel = null;
    private iterable $formatters = [];

    public function withLevel(string $level): self
    {
        $this->level = $level;

        return $this;
    }

    public function withTCPTransport(?string $host = null, ?int $port = null): self
    {
        $this->transport = 'tcp';
        $this->host = $host;
        $this->port = $port;

        return $this;
    }

    public function withAMQPTransport(string $queue, string $channel, string $vhost, ?string $host = null, ?int $port = null, ?float $timeout = null): self
    {
        $this->transport = 'amqp';
        $this->queue = $queue;
        $this->channel = $channel;
        $this->vhost = $vhost;
        $this->host = $host;
        $this->port = $port;
        $this->timeout = $timeout;

        return $this;
    }

    public function withFormatters(Node\Expr ...$formatters): self
    {
        array_push($this->formatters, ...$formatters);

        return $this;
    }

    public function getNode(): Node\Expr
    {
        $arguments = [];

        if (null !== $this->level) {
            $arguments[] = new Node\Arg(
                value: new Node\Scalar\String_($this->level),
                name: new Node\Identifier('level'),
            );
        }

        if (null !== $this->level) {
            $arguments[] = new Node\Arg(
                value: new Node\Expr\New_(
                    class: new Node\Name\FullyQualified('Gelf\Publisher'),
                    args: [
                        new Node\Arg(
                            value: $this->buildTransport(),
                            name: new Node\Identifier('transport'),
                        ),
                    ],
                ),
                name: new Node\Identifier('publisher'),
            );
        }

        $instance = new Node\Expr\New_(
            class: new Node\Name\FullyQualified('Monolog\Handler\GelfHandler'),
            args: $arguments,
        );

        foreach ($this->formatters as $formatter) {
            $instance = new Node\Expr\MethodCall(
                var: $instance,
                name: new Node\Identifier('setFormatter'),
                args: [
                    new Node\Arg($formatter),
                ],
            );
        }

        return $instance;
    }

    private function buildTransport(): Node\Expr
    {
        if ('amqp' === $this->transport) {
            return $this->buildAMQPTransport();
        }

        return $this->buildTCPTransport();
    }

    private function buildTCPTransport(): Node\Expr
    {
        $arguments = [];

        if (null !== $this->host) {
            $arguments[] = new Node\Arg(
                value: new Node\Scalar\String_($this->host),
                name: new Node\Identifier('host'),
            );
        }

        if (null !== $this->port) {
            $arguments[] = new Node\Arg(
                value: new Node\Scalar\LNumber($this->port),
                name: new Node\Identifier('port'),
            );
        }

        return new Node\Expr\New_(
            class: new Node\Name\FullyQualified('Gelf\Transport\TcpTransport'),
            args: $arguments,
        );
    }

    private function buildAMQPTransport(): Node\Expr
    {
        $arguments = [];

        if (null !== $this->host) {
            $arguments[] = new Node\Expr\ArrayItem(
                value: new Node\Scalar\String_($this->host),
                key: new Node\Scalar\String_('host'),
            );
        }

        if (null !== $this->port) {
            $arguments[] = new Node\Expr\ArrayItem(
                value: new Node\Scalar\LNumber($this->port),
                key: new Node\Scalar\String_('port'),
            );
        }

        if (null !== $this->vhost) {
            $arguments[] = new Node\Expr\ArrayItem(
                value: new Node\Scalar\String_($this->vhost),
                key: new Node\Scalar\String_('vhost'),
            );
        }

        if (null !== $this->timeout) {
            $arguments[] = new Node\Expr\ArrayItem(
                value: new Node\Scalar\LNumber((int) round($this->timeout, 0, \PHP_ROUND_HALF_UP)),
                key: new Node\Scalar\String_('read_timeout'),
            );
            $arguments[] = new Node\Expr\ArrayItem(
                value: new Node\Scalar\LNumber((int) round($this->timeout, 0, \PHP_ROUND_HALF_UP)),
                key: new Node\Scalar\String_('write_timeout'),
            );
            $arguments[] = new Node\Expr\ArrayItem(
                value: new Node\Scalar\LNumber((int) round($this->timeout, 0, \PHP_ROUND_HALF_UP)),
                key: new Node\Scalar\String_('connect_timeout'),
            );
        }

        return new Node\Expr\FuncCall(
            new Node\Expr\Closure([
                'stmts' => [
                    new Node\Stmt\Expression(
                        new Node\Expr\Assign(
                            new Node\Expr\Variable('channel'),
                            new Node\Expr\New_(
                                class: new Node\Name\FullyQualified('AMQPChannel'),
                                args: [
                                    new Node\Arg(
                                        value: new Node\Expr\New_(
                                            class: new Node\Name\FullyQualified('AMQPConnection'),
                                            args: [
                                                new Node\Arg(
                                                    new Node\Expr\Array_(
                                                        items: $arguments,
                                                        attributes: [
                                                            'kind' => Node\Expr\Array_::KIND_SHORT,
                                                        ]
                                                    )
                                                ),
                                            ]
                                        )
                                    ),
                                ],
                            ),
                        ),
                    ),

                    new Node\Stmt\Return_(
                        new Node\Expr\New_(
                            class: new Node\Name\FullyQualified('Gelf\Transport\AmqpTransport'),
                            args: [
                                new Node\Arg(
                                    value: new Node\Expr\New_(
                                        class: new Node\Name\FullyQualified('AMQPExchange'),
                                        args: [
                                            new Node\Arg(
                                                value: new Node\Expr\Variable('channel'),
                                            ),
                                        ],
                                    ),
                                    name: new Node\Identifier('exchange'),
                                ),
                                new Node\Arg(
                                    value: new Node\Expr\New_(
                                        class: new Node\Name\FullyQualified('AMQPQueue'),
                                        args: [
                                            new Node\Arg(
                                                value: new Node\Expr\Variable('channel'),
                                            ),
                                        ],
                                    ),
                                    name: new Node\Identifier('queue'),
                                ),
                            ],
                        ),
                    ),
                ],
            ]),
        );
    }
}
