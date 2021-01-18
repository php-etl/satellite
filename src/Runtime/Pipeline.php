<?php declare(strict_types=1);

namespace Kiboko\Component\Satellite\Runtime;

use Kiboko\Component\Config\ArrayBuilder;
use Kiboko\Component\FastMap;
use Kiboko\Component\Metadata\ClassReferenceMetadata;
use Kiboko\Component\Satellite\SatelliteInterface;
use PhpParser\Node;

final class Pipeline implements RuntimeInterface
{
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function prepare(SatelliteInterface $satellite): void
    {
    }

    private function buildPipeline(array $config): array
    {
        $pipeline = new Node\Expr\New_(
            new Node\Name('Pipeline\\Pipeline'),
            [
                new Node\Arg(
                    new Node\Expr\New_(
                        new Node\Name('Pipeline\\PipelineRunner'),
                    ),
                ),
            ],
        );

        foreach ($config as $step) {
            if (isset($step['extract'])) {
                $pipeline = $this->buildPipelineExtractor($pipeline, $step);
            } else if (isset($step['transform'])) {
                $pipeline = $this->buildPipelineTransformer($pipeline, $step);
            } else if (isset($step['load'])) {
                $pipeline = $this->buildPipelineLoader($pipeline, $step);
            }
        }

        return [
            new Node\Stmt\Expression(
                new Node\Expr\Assign(
                    new Node\Expr\Variable('pipeline'),
                    $pipeline
                ),
            ),

            new Node\Stmt\Expression(
                new Node\Expr\MethodCall(
                    new Node\Expr\Variable('pipeline'),
                    'run'
                )
            )
        ];
    }

    private function buildPipelineExtractor(Node\Expr $pipeline, array $config): Node\Expr
    {
        return new Node\Expr\MethodCall(
            $pipeline,
            'extract',
            [
                new Node\Arg(
                    new Node\Expr\New_(
                        new Node\Name\FullyQualified($config['extract'])
                    )
                )
            ]
        );
    }

    private function buildPipelineTransformer(Node\Expr $pipeline, array $config): Node\Expr
    {
        if (isset($config['array'])) {
            $mapper = $this->buildArrayMapper($config['array']);
        } else if (isset($config['object'])) {
            $mapper = $this->buildObjectMapper($config['object']);
        }

        return new Node\Expr\MethodCall(
            $pipeline,
            'transform',
            [
                new Node\Arg(
                    new Node\Expr\New_(
                        new Node\Name\FullyQualified($config['transform']),
                        [
                            new Node\Arg(
                                new Node\Expr\New_($mapper)
                            )
                        ]
                    )
                )
            ]
        );
    }

    private function buildPipelineLoader(Node\Expr $pipeline, array $config): Node\Expr
    {
        return new Node\Expr\MethodCall(
            $pipeline,
            'load',
            [
                new Node\Arg(
                    new Node\Expr\New_(
                        new Node\Name\FullyQualified($config['load'])
                    )
                )
            ]
        );
    }

    private function buildArrayMapper(array $config): Node
    {
        $builder = new ArrayBuilder();
        $node = $builder->children();
        foreach ($config as $fieldMapping) {
            if (isset($fieldMapping['copy'])) {
                $node->copy($fieldMapping['field'], $fieldMapping['copy']);
            } else if (isset($fieldMapping['expression'])) {
                $node->expression($fieldMapping['field'], $fieldMapping['expression']);
            } else if (isset($fieldMapping['constant'])) {
                $node->constant($fieldMapping['field'], $fieldMapping['constant']);
            }
        }
        $node = $node->end();

        return (new Pipeline\Spaghetti(
            new FastMap\PropertyAccess\EmptyPropertyPath(),
            $node->getMapper()
        ))->getNode();
    }

    public function build(): array
    {
        return [
            new Node\Stmt\Namespace_(new Node\Name('Foo')),
            new Node\Stmt\Expression(
                new Node\Expr\Include_(
                    new Node\Expr\BinaryOp\Concat(
                        new Node\Scalar\MagicConst\Dir(),
                        new Node\Scalar\String_('/vendor/autoload.php')
                    ),
                    Node\Expr\Include_::TYPE_REQUIRE
                ),
            ),
            new Node\Stmt\Use_([new Node\Stmt\UseUse(new Node\Name('Kiboko\\Component\\Pipeline'))]),

            ...$this->buildPipeline($this->config['steps'])
        ];
    }
}
