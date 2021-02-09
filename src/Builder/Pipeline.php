<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Builder;

use PhpParser\Builder;
use PhpParser\Node;

final class Pipeline implements Builder
{
    private array $steps = [];

    public function addExtractor(Node\Expr $extractor): self
    {
        array_push($this->steps, function (Node\Expr $pipeline) use ($extractor) {
            return new Node\Expr\MethodCall(
                var: $pipeline,
                name: new Node\Identifier('extract'),
                args: [
                    new Node\Arg($extractor)
                ]
            );
        });

        return $this;
    }

    public function addTransformer(Node\Expr $transformer): self
    {
        array_push($this->steps, function (Node\Expr $pipeline) use ($transformer) {
            return new Node\Expr\MethodCall(
                var: $pipeline,
                name: new Node\Identifier('transform'),
                args: [
                    new Node\Arg($transformer)
                ]
            );
        });

        return $this;
    }

    public function addLoader(Node\Expr $loader): self
    {
        array_push($this->steps, function (Node\Expr $pipeline) use ($loader) {
            return new Node\Expr\MethodCall(
                var: $pipeline,
                name: new Node\Identifier('load'),
                args: [
                    new Node\Arg($loader)
                ]
            );
        });

        return $this;
    }

    public function getNode(): Node
    {
        $pipeline = new Node\Expr\New_(
            new Node\Name\FullyQualified('Kiboko\\Component\\Pipeline\\Pipeline'),
            [
                new Node\Arg(
                    new Node\Expr\New_(
                        class: new Node\Name\FullyQualified('Kiboko\\Component\\Pipeline\\PipelineRunner'),
                        args: [
                            new Node\Arg(
                                value: new Node\Expr\New_(
                                    class: new Node\Name\FullyQualified('Psr\\Log\\NullLogger'),
                                )
                            )
                        ],
                    ),
                ),
            ],
        );

        foreach ($this->steps as $step) {
            $pipeline = $step($pipeline);
        }

        return $pipeline;
    }
}
