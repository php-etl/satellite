<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Plugin\SFTP\Builder;

use Kiboko\Component\Satellite\ExpressionLanguage\ExpressionLanguage;
use Kiboko\Contract\Configurator\StepBuilderInterface;
use PhpParser\Node;
use Symfony\Component\ExpressionLanguage\Expression;
use function Kiboko\Component\SatelliteToolbox\Configuration\compileValueWhenExpression;

final class Loader implements StepBuilderInterface
{
    private ?Node\Expr $logger;
    private ?Node\Expr $rejection;
    private ?Node\Expr $state;
    private iterable $servers;
    private iterable $putStatements;

    public function __construct(private ExpressionLanguage $interpreter)
    {
        $this->servers = [];
        $this->putStatements = [];
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

    public function withServer(Node\Expr ...$server): self
    {
        array_push($this->servers, ...$server);

        return $this;
    }

    public function withPut(
        string|Expression $path,
        string|Expression $content,
        string|Expression|null $mode,
        string|Expression|null $condition,
    ): self {
        $this->putStatements[] = [$path, $content, $mode, $condition];

        return $this;
    }

    private function compilePutStatements(): iterable
    {
        foreach ($this->putStatements as [$path, $content, $mode, $condition]) {
            if ($condition === null) {
                yield new Node\Stmt\Expression(
                    new Node\Expr\MethodCall(
                        new Node\Expr\PropertyFetch(
                            new Node\Expr\Variable('this'),
                            new Node\Identifier('connection')
                        ),
                        new Node\Identifier('put')
                    )
                );
            } else {
                yield new Node\Stmt\If_(
                    cond: compileValueWhenExpression($this->interpreter, $condition),
                    subNodes: [
                        'stmts' => [
                            new Node\Stmt\Expression(
                                new Node\Expr\MethodCall(
                                    new Node\Expr\PropertyFetch(
                                        new Node\Expr\Variable('this'),
                                        new Node\Identifier('connection')
                                    ),
                                    new Node\Identifier('put')
                                )
                            )
                        ],
                    ]
                );
            }
        }
    }

    public function getNode(): Node
    {
        return new Node\Expr\New_(
            class: new Node\Stmt\Class_(
                name: null,
                subNodes: [
                    'implements' => [
                        new Node\Name\FullyQualified('Kiboko\\Contract\\Pipeline\\TransformerInterface'),
                        new Node\Name\FullyQualified('Kiboko\\Contract\\Pipeline\\FlushableInterface'),
                    ],
                    'stmts' => [
                        new Node\Stmt\ClassMethod(
                            name: new Node\Identifier('__construct'),
                            subNodes: [
                                'flags' => Node\Stmt\Class_::MODIFIER_PUBLIC,
                                'stmts' => [
                                ],
                            ],
                        ),
                        new Node\Stmt\ClassMethod(
                            name: new Node\Identifier('transform'),
                            subNodes: [
                                'flags' => Node\Stmt\Class_::MODIFIER_PUBLIC,
                                'stmts' => [
                                    new Node\Stmt\Expression(
                                        expr: new Node\Expr\Assign(
                                            var: new Node\Expr\Variable('input'),
                                            expr: new Node\Expr\Yield_(),
                                        ),
                                    ),
                                    new Node\Stmt\Do_(
                                        cond: new Node\Expr\Assign(
                                            var: new Node\Expr\Variable('line'),
                                            expr: new Node\Expr\Yield_(
                                                value: new Node\Expr\New_(
                                                    class: new Node\Name\FullyQualified('Kiboko\\Component\\Bucket\\AcceptanceResultBucket'),
                                                    args: [
                                                        new Node\Arg(
                                                            new Node\Expr\Variable('input'),
                                                        ),
                                                    ],
                                                ),
                                            ),
                                        ),
                                        stmts: [
                                            ...$this->compilePutStatements(),
                                        ],
                                    ),
                                    new Node\Expr\Yield_(
                                        value: new Node\Expr\New_(
                                            class: new Node\Name\FullyQualified('Kiboko\\Component\\Bucket\\AcceptanceResultBucket'),
                                            args: [
                                                new Node\Arg(
                                                    new Node\Expr\Variable('input'),
                                                ),
                                            ],
                                        ),
                                    ),
                                ],
                                'returnType' => new Node\Name\FullyQualified('Generator'),
                            ],
                        ),
                    ],
                ],
            ),
        );
    }
}
