<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\Logger\Builder;

use PhpParser\Builder;
use PhpParser\Node;

final class MonologLogger implements Builder
{
    private iterable $handlers = [];
    private iterable $processors = [];

    public function __construct(private readonly string $channel) {}

    public function withHandlers(Node\Expr ...$handlers): self
    {
        array_push($this->handlers, ...$handlers);

        return $this;
    }

    public function withProcessors(Node\Expr ...$processors): self
    {
        array_push($this->processors, ...$processors);

        return $this;
    }

    public function getNode(): Node\Expr
    {
        $instance = new Node\Expr\New_(
            class: new Node\Name\FullyQualified('Monolog\\Logger'),
            args: [
                new Node\Arg(
                    new Node\Scalar\String_($this->channel)
                ),
            ]
        );

        $instance = new Node\Expr\MethodCall(
            var: $instance,
            name: new Node\Identifier('setHandlers'),
            args: [
                new Node\Arg(
                    new Node\Expr\Array_(
                        items: array_map(fn (Node $handler) => new Node\Expr\ArrayItem(value: $handler), $this->handlers),
                        attributes: [
                            'kind' => Node\Expr\Array_::KIND_SHORT,
                        ]
                    ),
                ),
            ],
        );

        $instance = new Node\Expr\MethodCall(
            var: $instance,
            name: new Node\Identifier('pushProcessor'),
            args: [
                new Node\Arg(
                    new Node\Expr\New_(
                        class: new Node\Name\FullyQualified('Monolog\\Processor\\PsrLogMessageProcessor')
                    )
                ),
            ],
        );

        $instance = new Node\Expr\MethodCall(
            var: $instance,
            name: new Node\Identifier('pushProcessor'),
            args: [
                new Node\Arg(
                    new Node\Expr\New_(
                        class: new Node\Name\FullyQualified('Monolog\\Processor\\MemoryUsageProcessor')
                    )
                ),
            ],
        );

        foreach ($this->processors as $processor) {
            $instance = new Node\Expr\MethodCall(
                var: $instance,
                name: new Node\Identifier('pushProcessor'),
                args: [
                    new Node\Arg($processor),
                ],
            );
        }

        return $instance;
    }
}
