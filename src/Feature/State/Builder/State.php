<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\State\Builder;

use PhpParser\Builder;
use PhpParser\Node;

final class State implements Builder
{
    private ?Node\Expr $state = null;

    public function withState(Node\Expr $state): self
    {
        $this->state = $state;

        return $this;
    }

    private static function nullState(): Node\Expr
    {
        return new Node\Expr\New_(
            new Node\Name\FullyQualified(\Kiboko\Contract\Pipeline\NullState::class)
        );
    }

    public function getNode(): Node\Expr
    {
        return $this->state ?? self::nullState();
    }
}
