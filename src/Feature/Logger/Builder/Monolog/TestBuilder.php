<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\Logger\Builder\Monolog;

use PhpParser\Node;

final class TestBuilder implements MonologBuilderInterface
{
    private ?string $level;
    private ?int $filePermissions;
    private ?bool $useLocking;
    private iterable $formatters;

    public function getNode(): Node\Expr
    {
        return new Node\Expr\New_(
            class: new Node\Name\FullyQualified('Monolog\\Handler\\TestHandler'),
        );
    }
}
