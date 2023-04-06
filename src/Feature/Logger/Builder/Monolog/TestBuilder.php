<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\Logger\Builder\Monolog;

use PhpParser\Node;

final readonly class TestBuilder implements MonologBuilderInterface
{
    public function getNode(): Node\Expr
    {
        return new Node\Expr\New_(
            class: new Node\Name\FullyQualified('Monolog\\Handler\\TestHandler'),
        );
    }
}
