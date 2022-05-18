<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\FTP\Builder;

use Kiboko\Component\SatelliteToolbox\Builder\IsolatedCodeBuilder;
use Kiboko\Contract\Configurator\StepBuilderInterface;
use PhpParser\Node;

final class Server implements StepBuilderInterface
{
    private ?Node\Expr $logger;
    private ?Node\Expr $rejection;
    private ?Node\Expr $state;
    private ?Node\Expr $username;
    private ?Node\Expr $password;
    private ?Node\Expr $passifMode;
    private ?Node\Expr $basePath;

    public function __construct(
        private Node\Expr $host,
        private ?Node\Expr $port = null,
        private ?Node\Expr $timeout = null
    ) {
        $this->logger = null;
        $this->rejection = null;
        $this->state = null;
        $this->username = null;
        $this->password = null;
        $this->passifMode = null;
        $this->basePath = null;
    }

    public function withLogger(Node\Expr $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    public function withRejection(Node\Expr $rejection): self
    {
        $this->rejection = $rejection;

        return $this;
    }

    public function withState(Node\Expr $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function getHost(): Node\Expr
    {
        return $this->host;
    }

    public function getBasePath(): Node\Expr
    {
        return $this->basePath;
    }

    public function withPort(Node\Expr $port): self
    {
        $this->port = $port;

        return $this;
    }

    public function withTimeout(Node\Expr $timeout): self
    {
        $this->timeout = $timeout;

        return $this;
    }

    public function withPassifMode(Node\Expr $passifMode): self
    {
        $this->passifMode = $passifMode;

        return $this;
    }

    public function withBasePath(Node\Expr $basePath): self
    {
        $this->basePath = $basePath;

        return $this;
    }

    public function withPasswordAuthentication(Node\Expr $username, Node\Expr $password): self
    {
        $this->username = $username;
        $this->password = $password;

        return $this;
    }

    private function compileAuthentication(): iterable
    {
        if (null !== $this->password) {
            yield new Node\Stmt\Expression(
                new Node\Expr\FuncCall(
                    name: new Node\Name('ftp_login'),
                    args: [
                        new Node\Arg(
                            new Node\Expr\Variable('connection'),
                        ),
                        new Node\Arg(
                            $this->username,
                        ),
                        new Node\Arg(
                            $this->password,
                        ),
                    ],
                ),
            );
        }
    }

    public function getNode(): Node
    {
        return (new IsolatedCodeBuilder(
            [
                new Node\Stmt\Expression(
                    new Node\Expr\Assign(
                        var: new Node\Expr\Variable('connection'),
                        expr: new Node\Expr\FuncCall(
                            name: new Node\Name('ftp_connect'),
                            args: [
                                new Node\Arg(
                                    $this->host,
                                ),
                                new Node\Arg(
                                    $this->port,
                                ),
                                new Node\Arg(
                                    $this->timeout,
                                ),
                            ],
                        ),
                    ),
                ),
                ...$this->compileAuthentication(),
                new Node\Stmt\Expression(
                    expr: new Node\Expr\FuncCall(
                        name: new Node\Name('ftp_pasv'),
                        args: [
                            new Node\Arg(
                                value: new Node\Expr\Variable('connection')
                            ),
                            new Node\Arg(
                                $this->passifMode,
                            ),
                        ]
                    )
                ),
                new Node\Stmt\Return_(
                    expr: new Node\Expr\Variable('connection')
                ),
            ]
        ))->getNode();
    }
}
