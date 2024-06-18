<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\Logger\Builder\Monolog;

use Kiboko\Component\Satellite\ExpressionLanguage as Satellite;
use PhpParser\Node;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

use function Kiboko\Component\SatelliteToolbox\Configuration\compileValueWhenExpression;

final class ElasticSearchBuilder implements MonologBuilderInterface
{
    private ?string $level = null;
    private ?string $index = null;
    private iterable $hosts = [];
    private iterable $formatters = [];

    public function __construct(private readonly ExpressionLanguage $interpreter = new Satellite\ExpressionLanguage())
    {
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

    public function withHosts(array|Expression|string ...$hosts): self
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
                            class: new Node\Name\FullyQualified('Elasticsearch\ClientBuilder'),
                            name: new Node\Identifier('create'),
                        ),
                        name: new Node\Identifier('setHosts'),
                        args: [
                            new Node\Arg(
                                value: compileValueWhenExpression($this->interpreter, $this->hosts),
                            ),
                        ],
                    ),
                    name: new Node\Identifier('build'),
                ),
                name: new Node\Identifier('client'),
            ),
        ];

        if (null !== $this->level) {
            $arguments[] = new Node\Arg(
                value: new Node\Scalar\String_($this->level),
                name: new Node\Identifier('level'),
            );
        }

        if (null !== $this->index) {
            $arguments[] = new Node\Arg(
                value: new Node\Expr\Array_(
                    items: [
                        new Node\Expr\ArrayItem(
                            value: new Node\Scalar\String_($this->index),
                            key: new Node\Scalar\String_('index'),
                        ),
                    ],
                    attributes: [
                        'kind' => Node\Expr\Array_::KIND_SHORT,
                    ]
                ),
                name: new Node\Identifier('options'),
            );
        }

        $instance = new Node\Expr\New_(
            class: new Node\Name\FullyQualified('Monolog\Handler\ElasticsearchHandler'),
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
}
