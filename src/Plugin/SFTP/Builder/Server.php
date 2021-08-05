<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\SFTP\Builder;

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
    private ?Node\Expr $publicKey;
    private ?Node\Expr $privateKey;
    private ?Node\Expr $privateKeyPassphrase;
    private ?Node\Expr $basePath;

    public function __construct(
        private Node\Expr $host,
        private ?Node\Expr $port = null
    ) {
        $this->logger = null;
        $this->rejection = null;
        $this->state = null;
        $this->username = null;
        $this->password = null;
        $this->publicKey = null;
        $this->privateKey = null;
        $this->privateKeyPassphrase = null;
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


    public function withPrivateKeyAuthentication(Node\Expr $username, Node\Expr $publicKey, Node\Expr $privateKey, ?Node\Expr $privateKeyPassphrase = null): self
    {
        $this->username = $username;
        $this->publicKey = $publicKey;
        $this->privateKey = $privateKey;
        $this->privateKeyPassphrase = $privateKeyPassphrase;

        return $this;
    }

    private function compileAuthentication(): iterable
    {
        if (null !== $this->password) {
            yield new Node\Stmt\Expression(
                new Node\Expr\FuncCall(
                    name: new Node\Name('ssh2_auth_password'),
                    args: [
                        new Node\Arg(
                            new Node\Expr\Variable('connection'),
                        ),
                        new Node\Arg(
                            $this->username,
                        ),
                        new Node\Arg(
                            $this->password,
                        )
                    ],
                ),
            );
        } elseif (null !== $this->privateKey) {
            yield new Node\Stmt\Expression(
                new Node\Expr\FuncCall(
                    name: new Node\Name('ssh2_auth_pubkey_file'),
                    args: [
                        new Node\Arg(
                            new Node\Expr\Variable('connection'),
                        ),
                        new Node\Arg(
                            $this->username,
                        ),
                        new Node\Arg(
                            $this->publicKey,
                        ),
                        new Node\Arg(
                            $this->privateKey,
                        ),
                        new Node\Arg(
                            $this->privateKeyPassphrase ?? new Node\Expr\ConstFetch(new Node\Name('null')),
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
                            name: new Node\Name('ssh2_connect'),
                            args: [
                                new Node\Arg(
                                    $this->host,
                                ),
                                new Node\Arg(
                                    $this->port,
                                )
                            ],
                        ),
                    ),
                ),
                ...$this->compileAuthentication(),
                new Node\Stmt\Return_(
                    new Node\Expr\FuncCall(
                        name: new Node\Name('ssh2_sftp'),
                        args: [
                            new Node\Arg(
                                new Node\Expr\Variable('connection'),
                            ),
                        ],
                    ),
                ),
            ]
        ))->getNode();
    }
}
