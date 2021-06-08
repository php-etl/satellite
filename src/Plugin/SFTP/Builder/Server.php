<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\SFTP\Builder;

use Kiboko\Component\SatelliteToolbox\Builder\IsolatedServerBuilder;
use PhpParser\Builder;
use PhpParser\Node;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use function Kiboko\Component\SatelliteToolbox\Configuration\compileValueWhenExpression;

final class Server implements Builder
{
    private string|Expression $host;
    private null|int|Expression $port;
    private null|string|Expression $username;
    private null|string|Expression $password;
    private null|string|Expression $publicKey;
    private null|string|Expression $privateKey;
    private null|string|Expression $privateKeyPassphrase;
    private null|string|Expression $basePath;

    public function __construct(
        string $host,
        private ExpressionLanguage $interpreter
    ) {
        $this->host = $host;
        $this->port = null;
        $this->username = null;
        $this->password = null;
        $this->publicKey = null;
        $this->privateKey = null;
        $this->privateKeyPassphrase = null;
        $this->basePath = null;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    public function withPort(int $port): self
    {
        $this->port = $port;

        return $this;
    }

    public function withBasePath(string $basePath): self
    {
        $this->basePath = $basePath;

        return $this;
    }

    public function withPasswordAuthentication(string $username, string $password): self
    {
        $this->username = $username;
        $this->password = $password;

        return $this;
    }


    public function withPrivateKeyAuthentication(string|Expression $username, string|Expression $publicKey, string|Expression $privateKey, null|string|Expression $privateKeyPassphrase = null): self
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
                            compileValueWhenExpression($this->interpreter, $this->username),
                        ),
                        new Node\Arg(
                            compileValueWhenExpression($this->interpreter, $this->password),
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
                            compileValueWhenExpression($this->interpreter, $this->username),
                        ),
                        new Node\Arg(
                            compileValueWhenExpression($this->interpreter, $this->publicKey),
                        ),
                        new Node\Arg(
                            compileValueWhenExpression($this->interpreter, $this->privateKey),
                        ),
                        new Node\Arg(
                            null !== $this->privateKeyPassphrase ? compileValueWhenExpression($this->interpreter, $this->privateKeyPassphrase) : new Node\Expr\ConstFetch(new Node\Name('null')),
                        )
                    ],
                ),
            );
        }
    }

    public function getNode(): Node
    {
        return (new IsolatedServerBuilder(
            [
                new Node\Stmt\Expression(
                    new Node\Expr\Assign(
                        var: new Node\Expr\Variable('connection'),
                        expr: new Node\Expr\FuncCall(
                            name: new Node\Name('ssh2_connect'),
                            args: [
                                new Node\Arg(
                                    compileValueWhenExpression($this->interpreter, $this->host),
                                ),
                                new Node\Arg(
                                    compileValueWhenExpression($this->interpreter, $this->port),
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
