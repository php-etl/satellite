<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Runtime\HttpHook;

use Kiboko\Component\Satellite;
use PhpParser\Node;
use PhpParser\PrettyPrinter;
use Psr\Log\LoggerInterface;

final class Runtime implements Satellite\Runtime\RuntimeInterface
{
    public function __construct(private array $config, private string $filename = 'function.php')
    {}

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function prepare(Satellite\SatelliteInterface $satellite, LoggerInterface $logger): void
    {
        $satellite->withFile(
            new Satellite\File($this->filename, new Satellite\Asset\InMemory(
                '<?php' . PHP_EOL . (new PrettyPrinter\Standard())->prettyPrint($this->build())
            )),
        );
    }

    public function build(): array
    {
        $service = new Satellite\Service();

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
            new Node\Stmt\Use_([new Node\Stmt\UseUse(new Node\Name('FastRoute'))]),
            new Node\Stmt\Use_([new Node\Stmt\UseUse(new Node\Name('Middlewares'))]),
            new Node\Stmt\Use_([new Node\Stmt\UseUse(new Node\Name('Nyholm\\Psr7'))]),
            new Node\Stmt\Use_([new Node\Stmt\UseUse(new Node\Name('Nyholm\\Psr7Server'))]),
            new Node\Stmt\Use_([new Node\Stmt\UseUse(new Node\Name('Psr'))]),
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
                        new Node\Name('Middlewares\\Utils\\Dispatcher'),
                        [
                            new Node\Arg(
                                new Node\Expr\Array_(
                                    [
                                        new Node\Expr\ArrayItem(
                                            new Node\Expr\New_(
                                                new Node\Name('Middlewares\\Uuid'),
                                            ),
                                        ),
                                        new Node\Expr\ArrayItem(
                                            new Node\Expr\New_(
                                                new Node\Name('Middlewares\\BasePath'),
                                                [
                                                    new Node\Arg(
                                                        new Node\Scalar\String_($this->config['path'] ?? '/')
                                                    )
                                                ],
                                            ),
                                        ),
                                        new Node\Expr\ArrayItem(
                                            $service->compile($this->config['http_hook'])->getNode(),
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
