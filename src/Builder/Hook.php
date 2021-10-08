<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Builder;

use PhpParser\Builder;
use PhpParser\Node;

final class Hook implements Builder
{
    public function __construct(
        private array $pipeline
    ) {
    }

    public function getNode(): Node
    {
        return new Node\Expr\Include_(
            new Node\Scalar\String_($this->pipeline['http_hook']['function']),
            Node\Expr\Include_::TYPE_REQUIRE,
        );
    }
}
