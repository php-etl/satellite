<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Feature\Logger\Builder;

use PhpParser\Builder;
use PhpParser\Node;

final class Logger implements Builder
{
    public function __construct(private ?Node\Expr $logger = null) {}

    public function withLogger(Node\Expr $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    public function getNode(): Node\Expr
    {
        if (null === $this->logger) {
            return new Node\Expr\New_(
                class: new Node\Name\FullyQualified(\Psr\Log\NullLogger::class),
            );
        }

        return $this->logger;
    }
}
