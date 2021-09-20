<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Custom\Builder;

use Kiboko\Contract\Configurator\StepBuilderInterface;
use PhpParser\Node;

final class Loader implements StepBuilderInterface
{
    private ?Node\Expr $service;
    private ?Node\Expr $logger;
    private ?Node\Expr $rejection;
    private ?Node\Expr $state;

    public function withService(Node\Expr $service): self
    {
        $this->service = $service;

        return $this;
    }

    public function withLogger(Node\Expr $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    public function withRejection(Node\Expr $rejection): self
    {
        $this->rejection = $rejection;

        return $this;
    }

    public function withState(Node\Expr $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function getNode(): Node
    {
        return new Node\Expr\FuncCall(
            new Node\Expr\Closure(
                subNodes: [
                    'returnType' => new Node\Name\FullyQualified('Kiboko\\Contract\\Pipeline\\LoaderInterface'),
                    'stmts' => [
                        new Node\Stmt\Expression(
                            new Node\Expr\Include_(
                                expr: new Node\Expr\BinaryOp\Concat(
                                    new Node\Expr\ConstFetch(
                                        new Node\Name\FullyQualified('__DIR__')
                                    ),
                                    new Node\Scalar\String_('/container.php'),
                                ),
                                type: Node\Expr\Include_::TYPE_REQUIRE,
                            ),
                        ),
                        new Node\Stmt\Return_(
                            expr: new Node\Expr\MethodCall(
                                var: new Node\Expr\New_(
                                    class: new Node\Name\FullyQualified('ProjectServiceContainer')
                                ),
                                name: new Node\Identifier('get'),
                                args: [
                                    new Node\Arg($this->service)
                                ]
                            )
                        )
                    ],
                ],
            ),
        );
    }
}
