<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Runtime\Pipeline;

use Kiboko\Component\Satellite;
use Kiboko\Component\Packaging;
use PhpParser\Builder;
use PhpParser\Node;
use PhpParser\PrettyPrinter;
use Psr\Log\LoggerInterface;

final class Runtime implements Satellite\Runtime\RuntimeInterface
{
    public function __construct(
        private array $config,
        private string $filename = 'pipeline.php'
    ) {
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function prepare(Satellite\SatelliteInterface $satellite, LoggerInterface $logger): void
    {
        $service = new Satellite\Service();
        $repository = $service->compile($this->config);

        $satellite->withFile(
            new Packaging\File($this->filename, new Packaging\Asset\InMemory(
                '<?php' . PHP_EOL . (new PrettyPrinter\Standard())->prettyPrint($this->build($repository->getBuilder()))
            )),
        );

        $satellite->withFile(
            ...$repository->getFiles(),
        );

        $satellite->dependsOn(...$repository->getPackages());
    }

    public function build(Builder $builder): array
    {
        return [
            new Node\Stmt\Return_(
                new Node\Expr\Closure(
                    subNodes: [
                        'static' => true,
                        'params' => [
                            new Node\Param(
                                var: new Node\Expr\Variable('runtime'),
                                type: new Node\Name\FullyQualified('Kiboko\\Component\\Satellite\\Console\\PipelineRuntimeInterface'),
                            )
                        ],
                        'stmts' => [
                            new Node\Stmt\Expression(
                                $builder->getNode()
                            ),
                        ]
                    ]
                ),
            ),
            new Node\Stmt\If_(
                cond: new Node\Expr\FuncCall(
                    name: new Node\Name('file_exists'),
                    args: [
                        new Node\Arg(
                            new Node\Expr\BinaryOp\Concat(
                                new Node\Scalar\MagicConst\Dir(),
                                new Node\Scalar\String_('/container.php')
                            ),
                        )
                    ]
                ),
                subNodes: [
                    'stmts' => [
                        new Node\Stmt\Expression(
                            new Node\Expr\Include_(
                                new Node\Expr\BinaryOp\Concat(
                                    new Node\Scalar\MagicConst\Dir(),
                                    new Node\Scalar\String_('/container.php')
                                ),
                                Node\Expr\Include_::TYPE_REQUIRE
                            ),
                        ),
                    ]
                ]
            ),
            new Node\Stmt\Expression(
                new Node\Expr\MethodCall(
                    var: $builder->getNode(),
                    name: 'run'
                ),
            )
        ];
    }
}
