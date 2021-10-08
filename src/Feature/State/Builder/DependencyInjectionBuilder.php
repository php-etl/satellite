<?php

namespace Kiboko\Component\Satellite\Feature\State\Builder;

use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use PhpParser\Builder;
use PhpParser\Node;
use function Kiboko\Component\SatelliteToolbox\AST\variable;

class DependencyInjectionBuilder implements Builder
{
    private ?Node\Expr $stepCode = null;
    private ?Node\Expr $stepName = null;

    public function __construct(
        private Node\Expr $service,
    ) {}

    public function withStepInfo(Node\Expr $stepName, Node\Expr $stepCode): self
    {
        $this->stepName = $stepName;
        $this->stepCode = $stepCode;

        return $this;
    }

    public function getNode(): Node\Expr
    {
        return new Node\Expr\MethodCall(
            var: new Node\Expr\MethodCall(
                var: new Node\Expr\MethodCall(
                    var: new Node\Expr\Variable('runtime'),
                    name: new Node\Name('container')
                ),
                name: new Node\Name('get'),
                args: [
                    new Node\Arg(
                        value: $this->service
                    )
                ]
            ),
            name: new Node\Name('stepState'),
            args: [
                new Node\Arg(
                    value: $this->stepCode
                ),
                new Node\Arg(
                    value: $this->stepName
                )
            ]
        );
    }
}
