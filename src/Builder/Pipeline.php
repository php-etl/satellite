<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Builder;

use PhpParser\Builder;
use PhpParser\Node;

final class Pipeline implements Builder
{
    private array $steps = [];

    public function __construct(
        private readonly Node\Expr $runtime
    ) {}

    public function addExtractor(
        Node\Expr|Builder $stepCode,
        Node\Expr|Builder $extractor,
        Node\Expr|Builder $rejection,
        Node\Expr|Builder $state,
    ): self {
        $this->steps[] = fn (Node\Expr $runtime) => new Node\Expr\MethodCall(
            var: $runtime,
            name: new Node\Identifier('extract'),
            args: [
                new Node\Arg($stepCode instanceof Builder ? $stepCode->getNode() : $stepCode),
                new Node\Arg($extractor instanceof Builder ? $extractor->getNode() : $extractor),
                new Node\Arg($rejection instanceof Builder ? $rejection->getNode() : $rejection),
                new Node\Arg($state instanceof Builder ? $state->getNode() : $state),
            ]
        );

        return $this;
    }

    public function addTransformer(
        Node\Expr|Builder $stepCode,
        Node\Expr|Builder $transformer,
        Node\Expr|Builder $rejection,
        Node\Expr|Builder $state,
    ): self {
        $this->steps[] = fn (Node\Expr $runtime) => new Node\Expr\MethodCall(
            var: $runtime,
            name: new Node\Identifier('transform'),
            args: [
                new Node\Arg($stepCode instanceof Builder ? $stepCode->getNode() : $stepCode),
                new Node\Arg($transformer instanceof Builder ? $transformer->getNode() : $transformer),
                new Node\Arg($rejection instanceof Builder ? $rejection->getNode() : $rejection),
                new Node\Arg($state instanceof Builder ? $state->getNode() : $state),
            ]
        );

        return $this;
    }

    public function addLoader(
        Node\Expr|Builder $stepCode,
        Node\Expr|Builder $loader,
        Node\Expr|Builder $rejection,
        Node\Expr|Builder $state,
    ): self {
        $this->steps[] = fn (Node\Expr $runtime) => new Node\Expr\MethodCall(
            var: $runtime,
            name: new Node\Identifier('load'),
            args: [
                new Node\Arg($stepCode instanceof Builder ? $stepCode->getNode() : $stepCode),
                new Node\Arg($loader instanceof Builder ? $loader->getNode() : $loader),
                new Node\Arg($rejection instanceof Builder ? $rejection->getNode() : $rejection),
                new Node\Arg($state instanceof Builder ? $state->getNode() : $state),
            ]
        );

        return $this;
    }

    public function getNode(): Node\Expr
    {
        $pipeline = $this->runtime;

        foreach ($this->steps as $step) {
            $pipeline = $step($pipeline);
        }

        return $pipeline;
    }
}
