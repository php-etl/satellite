<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Runtime\Runtime;

use Kiboko\Component\Satellite;
use PhpParser\Builder;
use PhpParser\Node;
use Psr\Log\LoggerInterface;
use Kiboko\Component\Packaging;
use PhpParser\PrettyPrinter;
use Kiboko\Contract\Configurator;

final class Runtime implements Satellite\Runtime\RuntimeInterface
{
    public function __construct(
        private array $config,
        private string $filename = 'runtime.php'
    ) {
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

    /**
     * @param Satellite\Builder\Pipeline\ConsoleRuntime $builder
     * @return array
     */
    public function build(Builder $builder): array
    {
        if ($this->config['pipeline']['services']) {
            $requireNode = new Node\Stmt\Expression(
                new Node\Expr\Include_(
                    expr: new Node\Expr\BinaryOp\Concat(
                        left: new Node\Scalar\MagicConst\Dir(),
                        right: new Node\Scalar\Encapsed(
                            parts: [
                                new Node\Scalar\EncapsedStringPart('/'),
                                new Node\Scalar\EncapsedStringPart('container.php')
                            ]
                        )
                    ),
                    type: Node\Expr\Include_::TYPE_REQUIRE
                )
            );
        }

        return array_filter([
            $requireNode ?? null,
            new Node\Stmt\Return_(
                $builder->withDependencyInjection($this->config['pipeline']['services'] ? true : false)->getNode()
            )
        ]);
    }
}
