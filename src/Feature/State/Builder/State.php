<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\State\Builder;

use PhpParser\Builder;
use PhpParser\Node;

final class State implements Builder
{
    public function __construct(private ?Node\Expr $state = null)
    {
    }

    public function withState(Node\Expr $state)
    {
        $this->state = $state;
    }

    private static function nullState(): Node\Expr
    {
        return new Node\Expr\New_(
            new Node\Name\FullyQualified('Kiboko\\Contract\\Pipeline\\NullState')
        );
    }

    public function getNode(): Node\Expr
    {
        return $this->state === null ? self::nullState() : $this->state;
    }
}
