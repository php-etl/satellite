<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Action\SFTP\Builder;

use PhpParser\Builder;
use PhpParser\Node;

final class Action implements Builder
{
    private ?Node\Expr $logger = null;
    private ?Node\Expr $state = null;

    public function __construct(
        private readonly Node\Expr $host,
        private readonly Node\Expr $port,
        private readonly Node\Expr $username,
        private readonly Node\Expr $password,
        private readonly Node\Expr $localFilePath,
        private readonly Node\Expr $serverFilePath,
    ) {
    }

    public function withLogger(Node\Expr $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    public function withState(Node\Expr $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function getNode(): Node
    {
        return new Node\Expr\New_(
            class: new Node\Name\FullyQualified('Kiboko\Component\Action\Flow\SFTP\Action'),
            args: [
                new Node\Arg(
                    value: $this->host,
                ),
                new Node\Arg(
                    value: $this->port,
                ),
                new Node\Arg(
                    value: $this->username,
                ),
                new Node\Arg(
                    value: $this->password,
                ),
                new Node\Arg(
                    value: $this->localFilePath,
                ),
                new Node\Arg(
                    value: $this->serverFilePath,
                )
            ]
        );
    }
}
