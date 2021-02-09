<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Stream\Builder;

use PhpParser\Builder;
use PhpParser\Node;

final class StderrLoader implements Builder
{
    public function getNode(): Node
    {
        return new Node\Expr\New_(
            class: new Node\Name\FullyQualified('Kiboko\\Component\\Pipeline\\Loader\\StderrLoader'),
        );
    }
}
