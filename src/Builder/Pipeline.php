<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Builder;

use PhpParser\Builder;
use PhpParser\Node;

final class Pipeline implements Builder
{
    private array $steps = [];

    public function __construct(
        private Node\Expr $runtime
    ) {}

    public function addExtractor(
        Node\Expr|Builder $extractor,
        Node\Expr|Builder $rejection,
        Node\Expr|Builder $state,
    ): self {
        array_push($this->steps, function (Node\Expr $runtime) use ($extractor, $rejection, $state) {
            return new Node\Expr\MethodCall(
                var: $runtime,
                name: new Node\Identifier('extract'),
                args: [
                    new Node\Arg($extractor instanceof Builder ? $extractor->getNode() : $extractor),
                    new Node\Arg($rejection instanceof Builder ? $rejection->getNode() : $rejection),
                    new Node\Arg($state instanceof Builder ? $state->getNode() : $state),
                ]
            );
        });

        return $this;
    }

    public function addTransformer(
        Node\Expr|Builder $transformer,
        Node\Expr|Builder $rejection,
        Node\Expr|Builder $state,
    ): self {
        array_push($this->steps, function (Node\Expr $runtime) use ($transformer, $rejection, $state) {
            return new Node\Expr\MethodCall(
                var: $runtime,
                name: new Node\Identifier('transform'),
                args: [
                    new Node\Arg($transformer instanceof Builder ? $transformer->getNode() : $transformer),
                    new Node\Arg($rejection instanceof Builder ? $rejection->getNode() : $rejection),
                    new Node\Arg($state instanceof Builder ? $state->getNode() : $state),
                ]
            );
        });

        return $this;
    }

    public function addLoader(
        Node\Expr|Builder $loader,
        Node\Expr|Builder $rejection,
        Node\Expr|Builder $state,
    ): self {
        array_push($this->steps, function (Node\Expr $runtime) use ($loader, $rejection, $state) {
            return new Node\Expr\MethodCall(
                var: $runtime,
                name: new Node\Identifier('load'),
                args: [
                    new Node\Arg($loader instanceof Builder ? $loader->getNode() : $loader),
                    new Node\Arg($rejection instanceof Builder ? $rejection->getNode() : $rejection),
                    new Node\Arg($state instanceof Builder ? $state->getNode() : $state),
                ]
            );
        });

        return $this;
    }

    public function getNode(): Node\Stmt
    {
        $pipeline = $this->runtime;

        foreach ($this->steps as $step) {
            $pipeline = $step($pipeline);
        }

        return new Node\Stmt\Expression($pipeline);
    }
}
