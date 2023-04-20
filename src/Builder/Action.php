<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Builder;

use PhpParser\Builder;
use PhpParser\Node;

final class Action implements Builder
{
    private ?Node\Expr $action = null;

    public function __construct(
        private readonly Node\Expr $runtime,
    ) {
    }

    public function addAction(
        Node\Expr|Builder $loader,
        Node\Expr|Builder $state,
    ): self {
        $this->action = new Node\Expr\MethodCall(
            var: $this->runtime,
            name: new Node\Identifier('execute'),
            args: [
                new Node\Arg($loader instanceof Builder ? $loader->getNode() : $loader),
                new Node\Arg($state instanceof Builder ? $state->getNode() : $state),
            ]
        );

        return $this;
    }

    public function getNode(): Node\Expr
    {
        return $this->action ?? $this->runtime;
    }
}
