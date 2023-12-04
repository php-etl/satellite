<?php

declare(strict_types=1);

namespace Kiboko\Component\Satellite\Runtime\Pipeline;

use Kiboko\Component\Packaging;
use Kiboko\Component\Satellite;
use Kiboko\Contract\Configurator;
use PhpParser\Builder;
use PhpParser\Node;
use PhpParser\PrettyPrinter;
use Psr\Log\LoggerInterface;

final readonly class Runtime implements Satellite\Runtime\RuntimeInterface
{
    public function __construct(
        private array $config,
        private string $filename = 'pipeline.php'
    ) {}

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function prepare(Configurator\FactoryInterface $service, Configurator\SatelliteInterface $satellite, LoggerInterface $logger): void
    {
        $repository = $service->compile($this->config);

        $satellite->withFile(
            new Packaging\File($this->filename, new Packaging\Asset\InMemory(
                '<?php'.\PHP_EOL.(new PrettyPrinter\Standard())->prettyPrint($this->build($repository->getBuilder()))
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
                                type: new Node\Name\FullyQualified('Kiboko\\Component\\Runtime\\Pipeline\\PipelineRuntimeInterface'),
                            ),
                        ],
                        'stmts' => [
                            new Node\Stmt\Expression(
                                $builder->getNode()
                            ),
                        ],
                    ]
                ),
            ),
        ];
    }
}
