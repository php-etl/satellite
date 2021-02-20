<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\Custom;

use PhpParser\Builder;
use PhpParser\Node;

final class CustomBuilder implements Builder
{
    public function __construct(private string $className)
    {}

    public function getNode(): Node
    {
        return new Node\Expr\New_(
            new Node\Name\FullyQualified($this->className),
        );
    }
}
