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
    ) {
    }

    public function addExtractor(
        Builder|Node\Expr $code,
        Builder|Node\Expr $extractor,
        Builder|Node\Expr $rejection,
        Builder|Node\Expr $state,
    ): self {
        $this->steps[] = fn (Node\Expr $runtime) => new Node\Expr\MethodCall(
            var: $runtime,
            name: new Node\Identifier('extract'),
            args: [
                new Node\Arg($code instanceof Builder ? $code->getNode() : $code),
                new Node\Arg($extractor instanceof Builder ? $extractor->getNode() : $extractor),
                new Node\Arg($rejection instanceof Builder ? $rejection->getNode() : $rejection),
                new Node\Arg($state instanceof Builder ? $state->getNode() : $state),
            ]
        );

        return $this;
    }

    public function addTransformer(
        Builder|Node\Expr $code,
        Builder|Node\Expr $transformer,
        Builder|Node\Expr $rejection,
        Builder|Node\Expr $state,
    ): self {
        $this->steps[] = fn (Node\Expr $runtime) => new Node\Expr\MethodCall(
            var: $runtime,
            name: new Node\Identifier('transform'),
            args: [
                new Node\Arg($code instanceof Builder ? $code->getNode() : $code),
                new Node\Arg($transformer instanceof Builder ? $transformer->getNode() : $transformer),
                new Node\Arg($rejection instanceof Builder ? $rejection->getNode() : $rejection),
                new Node\Arg($state instanceof Builder ? $state->getNode() : $state),
            ]
        );

        return $this;
    }

    public function addLoader(
        Builder|Node\Expr $code,
        Builder|Node\Expr $loader,
        Builder|Node\Expr $rejection,
        Builder|Node\Expr $state,
    ): self {
        $this->steps[] = fn (Node\Expr $runtime) => new Node\Expr\MethodCall(
            var: $runtime,
            name: new Node\Identifier('load'),
            args: [
                new Node\Arg($code instanceof Builder ? $code->getNode() : $code),
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
