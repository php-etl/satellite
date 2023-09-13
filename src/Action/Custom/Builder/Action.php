<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Action\Custom\Builder;

use Kiboko\Component\Satellite\Action\SFTP\Builder\ActionBuilderInterface;
use PhpParser\Node;

final class Action implements ActionBuilderInterface
{
    private ?Node\Expr $logger = null;
    private ?Node\Expr $state = null;

    public function __construct(private readonly Node\Expr $service, private readonly string $containerNamespace) {}

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
        return new Node\Expr\MethodCall(
            var: new Node\Expr\New_(
                class: new Node\Name\FullyQualified($this->containerNamespace)
            ),
            name: new Node\Identifier('get'),
            args: [
                new Node\Arg(
                    $this->service
                ),
            ]
        );
    }
}
