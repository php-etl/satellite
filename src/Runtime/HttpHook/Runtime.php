<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Runtime\HttpHook;

use Kiboko\Component\Satellite;
use Kiboko\Component\Packaging;
use PhpParser\Builder;
use PhpParser\Node;
use PhpParser\PrettyPrinter;
use Psr\Log\LoggerInterface;
use Kiboko\Contract\Configurator;

final class Runtime implements Satellite\Runtime\RuntimeInterface
{
    public function __construct(private array $config, private string $filename = 'hook.php')
    {
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function prepare(Configurator\FactoryInterface $service, Satellite\SatelliteInterface $satellite, LoggerInterface $logger): void
    {
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
            new Node\Stmt\Expression(
                new Node\Expr\Include_(
                    new Node\Expr\BinaryOp\Concat(
                        new Node\Scalar\MagicConst\Dir(),
                        new Node\Scalar\String_('/vendor/autoload.php')
                    ),
                    Node\Expr\Include_::TYPE_REQUIRE
                ),
            ),
            new Node\Stmt\Use_([new Node\Stmt\UseUse(new Node\Name('Middlewares\\BasePath'))]),
            new Node\Stmt\Use_([new Node\Stmt\UseUse(new Node\Name('Middlewares\\Utils\\Dispatcher'))]),
            new Node\Stmt\Use_([new Node\Stmt\UseUse(new Node\Name('Middlewares\\Uuid'))]),
            new Node\Stmt\Use_([new Node\Stmt\UseUse(new Node\Name('Nyholm\\Psr7'))]),
            new Node\Stmt\Use_([new Node\Stmt\UseUse(new Node\Name('Nyholm\\Psr7Server'))]),
            new Node\Stmt\Use_([new Node\Stmt\UseUse(new Node\Name('Laminas\\HttpHandlerRunner\\Emitter\\SapiEmitter'))]),

            new Node\Stmt\Expression(
                new Node\Expr\Assign(
                    new Node\Expr\Variable('psr17Factory'),
                    new Node\Expr\New_(new Node\Name('Psr7\\Factory\\Psr17Factory')),
                ),
            ),
            new Node\Stmt\Expression(
                new Node\Expr\Assign(
                    new Node\Expr\Variable('creator'),
                    new Node\Expr\New_(
                        new Node\Name('Psr7Server\\ServerRequestCreator'),
                        [
                            new Node\Arg(new Node\Expr\Variable('psr17Factory')),
                            new Node\Arg(new Node\Expr\Variable('psr17Factory')),
                            new Node\Arg(new Node\Expr\Variable('psr17Factory')),
                            new Node\Arg(new Node\Expr\Variable('psr17Factory')),
                        ]
                    ),
                ),
            ),
            new Node\Stmt\Expression(
                new Node\Expr\Assign(
                    new Node\Expr\Variable('request'),
                    new Node\Expr\MethodCall(
                        new Node\Expr\Variable('creator'),
                        'fromGlobals',
                    ),
                ),
            ),

            new Node\Stmt\Expression(
                new Node\Expr\Assign(
                    new Node\Expr\Variable('dispatcher'),
                    new Node\Expr\New_(
                        new Node\Name('Dispatcher'),
                        [
                            new Node\Arg(
                                new Node\Expr\Array_(
                                    [
                                        new Node\Expr\ArrayItem(
                                            new Node\Expr\New_(
                                                new Node\Name('Uuid'),
                                            ),
                                        ),
                                        new Node\Expr\ArrayItem(
                                            new Node\Expr\New_(
                                                new Node\Name('BasePath'),
                                                [
                                                    new Node\Arg(
                                                        new Node\Scalar\String_($this->config['path'] ?? '/')
                                                    )
                                                ],
                                            ),
                                        ),
                                        new Node\Expr\ArrayItem(
                                            $builder->getNode(),
                                        ),
                                    ],
                                    [
                                        'kind' => Node\Expr\Array_::KIND_SHORT
                                    ],
                                ),
                            ),
                        ],
                    ),
                ),
            ),

            new Node\Stmt\Expression(
                new Node\Expr\MethodCall(
                    new Node\Expr\New_(
                        new Node\Name('SapiEmitter')
                    ),
                    'emit',
                    [
                        new Node\Arg(
                            new Node\Expr\MethodCall(
                                new Node\Expr\Variable('dispatcher'),
                                'dispatch',
                                [
                                    new Node\Arg(
                                        new Node\Expr\Variable('request')
                                    )
                                ],
                            ),
                        ),
                    ],
                ),
            ),
        ];
    }
}
