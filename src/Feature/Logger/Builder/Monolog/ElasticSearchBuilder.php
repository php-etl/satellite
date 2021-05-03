<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\Logger\Builder\Monolog;

use PhpParser\Node;

final class ElasticSearchBuilder implements MonologBuilderInterface
{
    private ?string $level;
    private ?string $index;
    private iterable $hosts;
    private iterable $formatters;

    public function __construct()
    {
        $this->level = null;
        $this->index = null;
        $this->hosts = [];
        $this->formatters = [];
    }

    public function withLevel(string $level): self
    {
        $this->level = $level;

        return $this;
    }

    public function withIndex(string $index): self
    {
        $this->index = $index;

        return $this;
    }

    public function withHosts(string|array ...$hosts): self
    {
        array_push($this->hosts, ...$hosts);

        return $this;
    }

    public function withFormatters(Node\Expr ...$formatters): self
    {
        array_push($this->formatters, ...$formatters);

        return $this;
    }

    public function getNode(): Node\Expr
    {
        $arguments = [
            new Node\Arg(
                value: new Node\Expr\MethodCall(
                    var: new Node\Expr\MethodCall(
                        var: new Node\Expr\StaticCall(
                            class: new Node\Name\FullyQualified('Elasticsearch\\ClientBuilder'),
                            name: new Node\Identifier('create'),
                        ),
                        name: new Node\Identifier('setHosts'),
                        args: [
                            new Node\Arg(
                                value: $this->toAST($this->hosts),
                            ),
                        ],
                    ),
                    name: new Node\Identifier('build'),
                ),
                name: new Node\Identifier('client'),
            ),
        ];

        if ($this->level !== null) {
            $arguments[] = new Node\Arg(
                value: new Node\Scalar\String_($this->level),
                name: new Node\Identifier('level'),
            );
        }

        if ($this->index !== null) {
            $arguments[] = new Node\Arg(
                value: new Node\Expr\Array_(
                    items: [
                        new Node\Expr\ArrayItem(
                            value: new Node\Scalar\String_($this->index),
                            key: new Node\Scalar\String_('index'),
                        ),
                    ],
                    attributes: [
                        'kind' => Node\Expr\Array_::KIND_SHORT
                    ]
                ),
                name: new Node\Identifier('options'),
            );
        }

        $instance = new Node\Expr\New_(
            class: new Node\Name\FullyQualified('Monolog\\Handler\\ElasticSearchHandler'),
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

    private function toAST($value): Node\Expr
    {
        if (is_array($value)) {
            return new Node\Expr\Array_(
                items: array_map(
                    fn ($item, $key) => new Node\Expr\ArrayItem(
                        value: $this->toAST($item),
                        key: $this->toAST($key),
                    ),
                    $value,
                    array_keys($value)
                ),
                attributes: [
                    'kind' => Node\Expr\Array_::KIND_SHORT
                ]
            );
        }
        if (is_string($value)) {
            return new Node\Scalar\String_($value);
        }
        if (is_int($value)) {
            return new Node\Scalar\LNumber($value);
        }
        if (is_float($value)) {
            return new Node\Scalar\DNumber($value);
        }
        if (is_null($value)) {
            return new Node\Expr\ConstFetch(new Node\Name('null'));
        }

        throw new \InvalidArgumentException('Could not convert value into a valid AST');
    }
}
